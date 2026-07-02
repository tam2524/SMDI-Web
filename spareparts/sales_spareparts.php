<?php
session_start();
$adminRoles = ['Admin', 'Head', 'itsuperadmin', 'Admin Spareparts', 'Spareparts-Admin', 'Spareparts-Owner'];
$allowedRoles = array_merge($adminRoles, ['Spareparts-Sales', 'Spareparts-Retail']);
if (!isset($_SESSION['username']) || !in_array($_SESSION['position'], $allowedRoles)) {
    header('Location: ../login.html');
    exit();
}
$currentBranch = $_SESSION['user_branch'] ?? 'HEADOFFICE';
$userRole = $_SESSION['position'] ?? $_SESSION['user_role'] ?? 'user';
$adminRoles = ['Admin', 'Head', 'itsuperadmin', 'Admin Spareparts', 'Spareparts-Admin', 'Spareparts-Owner'];
$canDelete = in_array(strtolower(trim($userRole)), array_map('strtolower', $adminRoles));

require_once __DIR__ . '/../api/db_config.php';
$stmt = $conn->prepare("SELECT report_header_title FROM users WHERE username = ?");
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$dbUser = $stmt->get_result()->fetch_assoc();
$stmt->close();
$reportHeaderTitle = !empty($dbUser['report_header_title']) ? $dbUser['report_header_title'] : 'ROXAS CITY SOLID MERCHANDISING';

$userRoleLower = strtolower(trim($userRole));
$backLink = 'sales_dashboard.php';
if ($userRoleLower === 'spareparts-owner') {
    $backLink = 'owner_dashboard.php';
} elseif (in_array($userRoleLower, ['admin', 'head', 'itsuperadmin', 'admin spareparts', 'spareparts-admin'])) {
    $backLink = 'admin_dashboard.php';
} elseif ($userRoleLower === 'spareparts-warehouse') {
    $backLink = 'warehouse_dashboard.php';
}

$showTabs = !isset($_GET['tab']);
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
    <title>SMDI - ADMIN SPARE PARTS INVENTORY</title>
    <meta content='width=device-width, initial-scale=1.0' name='viewport'>
    <link rel='icon' href='../assets/img/smdi_logosmall.png' type='image/png'>
    <link rel='stylesheet' href='https://use.fontawesome.com/releases/v5.15.4/css/all.css' />
    <link href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css' rel='stylesheet'>
    <link href='../css/bootstrap.min.css' rel='stylesheet'>
    <link href='../css/style.css?v=<?php echo time(); ?>' rel='stylesheet'>
    <link href='../css/spareparts_inventory_style.css?v=<?php echo time(); ?>' rel='stylesheet'>
    <link href='../css/spareparts_premium.css?v=<?php echo time(); ?>' rel='stylesheet'>
    <link href="../css/spareparts_report_print.css?v=<?php echo time(); ?>" rel="stylesheet">
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

        @media print {

            .no-print,
            nav,
            .btn,
            .modal-header,
            .modal-footer,
            #mainTabs,
            .navbar,
            .btn-premium-save,
            .navbar-brand,
            .btn-primary {
                display: none !important;
            }

            .modal {
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                overflow: visible !important;
                display: block !important;
                width: 100% !important;
            }

            .modal-dialog {
                max-width: 100% !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .modal-content {
                border: none !important;
                box-shadow: none !important;
                background: white !important;
            }

            .modal-body {
                padding: 0 !important;
            }

            body {
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .table {
                width: 100% !important;
            }

            .d-print-block {
                display: block !important;
            }

            #ledgerModalBody {
                padding: 20px !important;
            }
        }

        /* sellPartsOutModal – true fullscreen forced */
        #sellPartsOutModal,
        #sellPartsOutModal .modal-dialog,
        #sellPartsOutModal .modal-content {
            width: 100vw !important;
            max-width: 100vw !important;
            margin: 0 !important;
            padding: 0 !important;
            border-radius: 0 !important;
        }

        #sellPartsOutModal,
        #sellPartsOutModal .modal-dialog,
        #sellPartsOutModal .modal-content {
            height: 100vh !important;
            min-height: 100vh !important;
        }

        #sellPartsOutModal .modal-content {
            display: flex !important;
            flex-direction: column !important;
        }

        #sellPartsOutModal .modal-body {
            flex: 1 1 auto !important;
            overflow-y: auto !important;
            display: flex !important;
            flex-direction: column !important;
        }
        #sellPartsOutModal .modal-body > .row {
            flex: 1 0 auto !important;
        }

        #sellPartsOutModal .sale-summary-sidebar {
            position: sticky;
            top: 20px;
            z-index: 10;
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
            border: 1px solid rgba(255, 255, 255, 0.5);
            color: white;
            background: transparent;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            text-decoration: none;
            transition: all 0.2s;
        }

        .navbar-custom .btn-logout:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        @media print {
            @page {
                size: portrait;
                margin: 0;
            }

            /* HIDE EVERYTHING BY DEFAULT EXCEPT PRINT-RELEVANT MODALS */
            body>*:not(#inventoryPreviewModal):not(#customerLedgerModal):not(#receiptModal),
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
            #inventoryPreviewModal,
            #customerLedgerModal,
            #receiptModal {
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
            #customerLedgerModal *,
            #receiptModal *,
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

        /* Premium Modal Styling */
        .modal-content {
            border: none;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            padding: 0 !important;
        }

        form {
            margin: 0 !important;
            padding: 0 !important;
            width: 100%;
        }

        .modal-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1.25rem 1.5rem;
        }

        .modal-header.bg-primary {
            background: linear-gradient(135deg, var(--smdi-green), var(--smdi-green-dark)) !important;
        }

        .modal-header.bg-success {
            background: linear-gradient(135deg, #10b981, #059669) !important;
        }

        .modal-header.bg-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626) !important;
        }

        .modal-header.bg-info {
            background: linear-gradient(135deg, #0ea5e9, #0284c7) !important;
        }

        .modal-title {
            font-weight: 700;
            letter-spacing: 0.5px;
            font-size: 1.1rem;
        }

        .modal-body {
            padding: 1.5rem 2rem;
        }

        .form-label {
            font-weight: 600;
            color: #4a5568;
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .form-control,
        .form-select {
            border-radius: 10px;
            padding: 0.65rem 1rem;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
            transition: all 0.2s ease;
        }

        .form-control:focus,
        .form-select:focus {
            background-color: white;
            border-color: var(--smdi-green);
            box-shadow: 0 0 0 4px rgba(0, 77, 64, 0.1);
        }

        .modal-footer {
            background-color: #f8fafc;
            border-top: 1px solid #edf2f7;
            padding: 1.25rem 2rem;
        }

        .btn-premium-save {
            background: var(--smdi-green);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            box-shadow: 0 4px 6px rgba(0, 77, 64, 0.2);
        }

        .btn-premium-save:hover {
            background: var(--smdi-green-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 77, 64, 0.3);
            color: white;
        }

        .btn-premium-cancel {
            background: #cbd5e1;
            color: #475569;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .btn-premium-cancel:hover {
            background: #94a3b8;
            color: #1e293b;
        }

        .input-group-text {
            background-color: #f1f5f9;
            border-color: #e2e8f0;
            color: #64748b;
            font-weight: 600;
            border-radius: 10px 0 0 10px;
        }

        /* Badge Styling inside table */
        .badge-balance {
            background-color: #fee2e2;
            color: #dc2626;
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-weight: 700;
        }
    </style>
    <script>window.canDelete = <?php echo $canDelete ? 'true' : 'false'; ?>;</script>
</head>

<body>
    <!-- INCOMING TRANSFER ALERT MODAL -->
    <div class="modal fade" id="incomingTransferAlertModal" tabindex="-1" aria-hidden="true" style="z-index: 2000;">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                <div class="modal-header bg-danger text-white border-0 py-3">
                    <h5 class="modal-title fw-bold text-white"><i
                            class="bi bi-exclamation-triangle-fill me-2"></i>PENDING INCOMING TRANSFERS</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-0" id="incoming-alert-modal-body"
                    style="background: #f8f9fa; max-height: 80vh; overflow-y: auto;">
                    <div class="p-5 text-center text-muted">
                        <div class="spinner-border text-danger mb-3" role="status"></div>
                        <p class="mb-0 fw-bold ls-1">Checking for new transfers...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <nav class="navbar navbar-custom sticky-top">
        <div class="container-fluid px-4">
            <div class="d-flex align-items-center">
                <a href="<?php echo htmlspecialchars($backLink); ?>" class="back-btn">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <span class="navbar-brand">
                    SALES - CUSTOMER MANAGEMENT
                </span>
            </div>
            <div class="user-info">
                <?php if (isset($_SESSION['username'])): ?>
                    <!-- Notification Bell -->
                    <a href="javascript:void(0)"
                        onclick="if(document.getElementById('incomingTransferAlertModal')) bootstrap.Modal.getOrCreateInstance(document.getElementById('incomingTransferAlertModal')).show()"
                        class="text-white text-decoration-none me-4 position-relative" title="Incoming Transfers">
                        <i class="bi bi-bell-fill fs-5"></i>
                        <span id="incoming-badge"
                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none"
                            style="font-size: 0.55rem; padding: 0.25em 0.5em;">0</span>
                    </a>
                    <span><i class="bi bi-person-circle me-1"></i> <?php echo htmlspecialchars($currentBranch); ?></span>
                    <?php
                endif; ?>
                <a href="../api/logout.php" class="btn-logout">LOGOUT</a>
            </div>
        </div>
    </nav>

    <main class='container-fluid pt-2 pb-4 px-lg-5'>
        <ul class='nav nav-pills custom-tabs mb-2 d-none' id='mainTabs' role='tablist' style="padding:0; gap:10px;">
            <li class='nav-item' role='presentation'>
                <button class='nav-link active' id='customers-tab' data-bs-toggle='pill' data-bs-target='#customers'
                    type='button'>
                    <i class="bi bi-people me-2"></i>Customers
                </button>
            </li>
            <li class='nav-item' role='presentation'>
                <button class='nav-link' id='sales-tab' data-bs-toggle='pill' data-bs-target='#sales' type='button'>
                    <i class="bi bi-cash-stack me-2"></i>Sales
                </button>
            </li>
            <li class='nav-item' role='presentation'>
                <button class='nav-link' id='payments-tab' data-bs-toggle='pill' data-bs-target='#payments'
                    type='button'>
                    <i class="bi bi-credit-card me-2"></i>Payments
                </button>
            </li>
            <li class='nav-item' role='presentation'>
                <button class='nav-link' id='returns-tab' data-bs-toggle='pill' data-bs-target='#returns' type='button'>
                    <i class="bi bi-arrow-return-left me-2"></i>Returns / CM
                </button>
            </li>
            <li class='nav-item' role='presentation'>
                <button class='nav-link' id='employees-tab' data-bs-toggle='pill' data-bs-target='#employees'
                    type='button'>
                    <i class="bi bi-person-badge me-2"></i>Employees
                </button>
            </li>
            <li class='nav-item' role='presentation'>
                <button class='nav-link' id='pricelists-tab' data-bs-toggle='pill' data-bs-target='#pricelists'
                    type='button'>
                    <i class="bi bi-tags me-2"></i>Pricelists
                </button>
            </li>
        </ul>

        <div class='tab-content' id='mainTabContent'>


            <!-- CUSTOMERS TAB -->
            <div class='tab-pane fade show active' id='customers' role='tabpanel'>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="mb-0 fw-bold" style="color: #4a5568 !important;"><i
                            class="bi bi-list-ul me-2"></i>Customer Ledger</h3>
                    <div class="d-flex gap-3 align-items-center">
                        <button class='btn btn-premium-save px-4 rounded-pill shadow-sm' data-bs-toggle='modal'
                            data-bs-target='#addCustomerModal'>
                            <i class='bi bi-person-plus-fill me-2'></i>Add New Customer
                        </button>
                        <input type='text' id='customersSearch' class='form-control rounded-pill px-4 shadow-sm'
                            style="width: 250px;" placeholder='Search by name, address...' autocomplete="off">
                    </div>
                </div>
                <div class='table-responsive mt-4'
                    style="border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); background: white;">
                    <table class='table table-borderless align-middle mb-0' id='customersTable'>
                        <thead style="background-color: var(--smdi-green-dark); color: white;">
                            <tr>
                                <th class="py-3 ps-4 text-uppercase fw-semibold"
                                    style="letter-spacing: 0.5px; font-size: 0.85rem;">Name</th>
                                <th class="py-3 text-uppercase fw-semibold"
                                    style="letter-spacing: 0.5px; font-size: 0.85rem;">Contact No</th>
                                <th class="py-3 text-uppercase fw-semibold"
                                    style="letter-spacing: 0.5px; font-size: 0.85rem;">Address</th>
                                <th class="py-3 pe-4 text-center text-uppercase fw-semibold"
                                    style="letter-spacing: 0.5px; font-size: 0.85rem;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id='customersTableBody'>
                            <!-- Populated via JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- RETURNS TAB (Credit Memo) -->
            <div class='tab-pane fade' id='returns' role='tabpanel'>
                <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                    <h4 class="mb-0 fw-bold"><i class="bi bi-arrow-return-left me-2 text-danger"></i>Credit Memo /
                        Returns</h4>
                    <div class="d-flex gap-3 align-items-center">
                        <button class='btn btn-sm btn-danger text-white fw-bold px-3 shadow-sm' data-bs-toggle='modal'
                            data-bs-target='#recordReturnModal'>
                            <i class='bi bi-patch-minus me-1'></i>Record Return (CM)
                        </button>
                        <input type='text' id='returnsSearch' class='form-control form-control-sm' style="width: 250px;"
                            placeholder='Search customer, OR, or part...' autocomplete="off">
                    </div>
                </div>

                <!-- Summary Stats -->
                <div class="row g-3 mb-4" id="returnsSummaryCards">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100"
                            style="border-left: 4px solid #dc3545 !important; border-radius: 10px;">
                            <div class="card-body d-flex align-items-center gap-3 py-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center"
                                    style="width:48px;height:48px;background:rgba(220,53,69,0.1);">
                                    <i class="bi bi-arrow-return-left text-danger fs-5"></i>
                                </div>
                                <div>
                                    <div class="small text-muted text-uppercase fw-bold" style="font-size:0.72rem;">
                                        Total Returns</div>
                                    <div class="fw-bold fs-4 text-danger" id="returnsStat-count">0</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100"
                            style="border-left: 4px solid #fd7e14 !important; border-radius: 10px;">
                            <div class="card-body d-flex align-items-center gap-3 py-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center"
                                    style="width:48px;height:48px;background:rgba(253,126,20,0.1);">
                                    <i class="bi bi-currency-exchange text-warning fs-5"></i>
                                </div>
                                <div>
                                    <div class="small text-muted text-uppercase fw-bold" style="font-size:0.72rem;">
                                        Total Credited</div>
                                    <div class="fw-bold fs-5 text-danger" id="returnsStat-credited">₱0.00</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100"
                            style="border-left: 4px solid #198754 !important; border-radius: 10px;">
                            <div class="card-body d-flex align-items-center gap-3 py-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center"
                                    style="width:48px;height:48px;background:rgba(25,135,84,0.1);">
                                    <i class="bi bi-box-arrow-in-down text-success fs-5"></i>
                                </div>
                                <div>
                                    <div class="small text-muted text-uppercase fw-bold" style="font-size:0.72rem;">
                                        Items Back in Stock</div>
                                    <div class="fw-bold fs-4 text-success" id="returnsStat-qty">0</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class='table-responsive'
                    style="border-radius:10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); overflow:hidden;">
                    <table class='table table-hover align-middle mb-0' id='returnsTable'>
                        <thead style="background: linear-gradient(135deg, #c0392b, #e74c3c); color:white;">
                            <tr>
                                <th class="py-3 ps-3">Date</th>
                                <th>Customer</th>
                                <th>SI #</th>
                                <th>Part Details</th>
                                <th class="text-center">Qty Returned</th>
                                <th class="text-end">Amount Credited</th>
                                <th>Remarks</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody id='returnsTableBody'>
                            <!-- Populated via JS -->
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top bg-white px-3">
                    <div class="text-muted small fw-semibold" id="returnsPageInfo"></div>
                    <nav>
                        <ul class="pagination pagination-sm mb-0 gap-1" id="returnsPagination"></ul>
                    </nav>
                </div>
            </div>


            <div class='tab-pane fade' id='employees' role='tabpanel'>
                <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                    <h4 class="mb-0 fw-bold"><i class="bi bi-person-badge me-2"></i>Employee Management</h4>
                    <div class="d-flex gap-3 align-items-center">
                        <button class='btn btn-sm btn-success text-white fw-bold px-3 shadow-sm' data-bs-toggle='modal'
                            data-bs-target='#manageSalesForceModal'>
                            <i class='bi bi-person-plus me-1'></i>Manage / Add Employees
                        </button>
                        <input type='text' id='employeesSearch' class='form-control form-control-sm'
                            style="width: 250px;" placeholder='Search employees...' autocomplete="off">
                    </div>
                </div>
                <div class='table-responsive'>
                    <table class='table table-hover align-middle' id='employeesTable'>
                        <thead>
                            <tr>
                                <th>Employee Information</th>
                                <th>Branch</th>
                                <th>Sales Volume</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id='employeesTableBody'>
                            <!-- Populated via JS -->
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                    <div class="text-muted small fw-semibold" id="employeesPageInfo"></div>
                    <nav>
                        <ul class="pagination pagination-sm mb-0 gap-1" id="employeesPagination"></ul>
                    </nav>
                </div>
            </div>

            <div class='tab-pane fade' id='pricelists' role='tabpanel'>
                <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                    <h4 class="mb-0 fw-bold"><i class="bi bi-tags me-2"></i>Pricelists per Rank Level</h4>
                    <div class="d-flex gap-3 align-items-center">
                        <button class="btn btn-sm btn-outline-primary px-3 fw-bold shadow-sm" data-bs-toggle="modal"
                            data-bs-target="#bulkPricelistModal">
                            <i class="bi bi-stack me-1"></i>Bulk Set Price
                        </button>
                        <button class="btn btn-sm btn-premium-save px-3 fw-bold shadow-sm" data-bs-toggle="modal"
                            data-bs-target="#savePricelistModal">
                            <i class="bi bi-plus-lg me-1"></i>Set Single Rank Price
                        </button>
                        <input type='text' id='pricelistsSearch' class='form-control form-control-sm border shadow-sm'
                            style="width: 250px;" placeholder='Search parts or rank...' autocomplete="off">
                    </div>
                </div>
                <div class='table-responsive'>
                    <table class='table table-hover align-middle border-0' id='pricelistsTable'>
                        <thead style="background-color: var(--smdi-green-dark); color: white;">
                            <tr>
                                <th class="py-3 ps-4">Part Details</th>
                                <th class="py-3">Rank Level</th>
                                <th class="py-3 text-end">Price</th>
                                <th class="py-3 text-center">Date Updated</th>
                                <th class="py-3 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id='pricelistsTableBody'>
                            <!-- Populated via JS -->
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                    <div class="text-muted small fw-semibold" id="pricelistsPageInfo"></div>
                    <nav>
                        <ul class="pagination pagination-sm mb-0 gap-1" id="pricelistsPagination"></ul>
                    </nav>
                </div>
            </div>

            <div class='tab-pane fade' id='sales' role='tabpanel'>
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
                        <input type='text' id='salesSearch' class='form-control form-control-sm border shadow-sm'
                            style="width: 250px;" placeholder='Search customer or OR...' autocomplete="off">
                    </div>
                </div>

                <!-- SALES SUB-TABS -->
                <div class="mb-4">
                    <ul class="nav nav-pills bg-light p-1 rounded shadow-sm d-inline-flex" id="salesSubTabs"
                        role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active py-2 px-4 fw-bold" id="all-sales-tab" data-bs-toggle="pill"
                                data-type="all" type="button">All
                                Transactions</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link py-2 px-4 fw-bold" id="cash-sales-tab" data-bs-toggle="pill"
                                data-type="cash" type="button">Cash Sales</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link py-2 px-4 fw-bold" id="charge-sales-tab" data-bs-toggle="pill"
                                data-type="charge" type="button">Charge Sales</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link py-2 px-4 fw-bold" id="pdc-sales-tab" data-bs-toggle="pill"
                                data-type="pdc" type="button">Charge w/ PDC</button>
                        </li>
                    </ul>
                </div>

                <div class='table-responsive'>
                    <table class='table table-hover align-middle' id='salesTable'>
                        <thead class="bg-light">
                            <tr>
                                <th>Date</th>
                                <th>Branch</th>
                                <th>Customer</th>
                                <th>SI #</th>
                                <th>Sales Force</th>
                                <th class="text-end">Total Amount</th>
                                <th class="text-center">Type</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id='salesTableBody'></tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center px-4 py-3 border-top bg-white">
                    <div class="text-muted small fw-semibold" id="salesPageInfo"></div>
                    <nav>
                        <ul class="pagination pagination-sm mb-0 gap-1" id="salesPagination"></ul>
                    </nav>
                </div>
            </div>

            <div class='tab-pane fade' id='payments' role='tabpanel'>
                <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                    <h4 class="mb-0 fw-bold"><i class="bi bi-cash-coin me-2"></i>Payment Management</h4>
                    <div class="d-flex gap-3 align-items-center">
                        <div class="btn-group shadow-sm">
                            <button class='btn btn-sm btn-primary text-white fw-bold px-3' data-bs-toggle='modal'
                                data-bs-target='#recordPaymentModal'>
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
                                <th class="text-center">SI # / REF</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id='paymentsTableBody'></tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3 px-3 py-3 border-top bg-white">
                    <div class="text-muted small fw-semibold" id="paymentsPageInfo"></div>
                    <nav>
                        <ul class="pagination pagination-sm mb-0 gap-1" id="paymentsPagination"></ul>
                    </nav>
                </div>
            </div>

            <div class='tab-pane fade' id='global-transfer' role='tabpanel'>
                <div class='d-flex justify-content-between align-items-center mb-4'>
                    <h4 class='mb-0 text-primary fw-bold'>GLOBAL TRANSFERS OVERVIEW</h4>
                    <div class="d-flex gap-2">
                        <div class="input-group" style="width: 300px;">
                            <input type="text" id="globalTransferSearch" class="form-control"
                                placeholder="Search REF, Branch, Part...">
                            <button class="btn btn-primary shadow-sm"><i class="bi bi-search"></i></button>
                        </div>
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
                                <h6 class='mb-0 fw-bold'><i class="bi bi-truck me-2"></i>IN-TRANSIT</h6>
                                <span class='badge rounded-pill px-3' id='count-in-transit'>0</span>
                            </div>
                            <div class='card-body p-3' id='col-in-transit' style="max-height: 75vh; overflow-y: auto;">
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
                                <h6 class='mb-0 fw-bold'><i class="bi bi-check-circle me-2"></i>COMPLETED
                                </h6>
                                <span class='badge rounded-pill px-3' id='count-completed'>0</span>
                            </div>
                            <div class='card-body p-3' id='col-completed' style="max-height: 75vh; overflow-y: auto;">
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
                                <h6 class='mb-0 fw-bold'><i class="bi bi-x-circle me-2"></i>REJECTED</h6>
                                <span class='badge rounded-pill px-3' id='count-rejected'>0</span>
                            </div>
                            <div class='card-body p-3' id='col-rejected' style="max-height: 75vh; overflow-y: auto;">
                                <div class="text-center text-muted p-5 mt-4">
                                    <div class="spinner-border spinner-border-sm text-danger mb-2"></div>
                                    <p class="small">Loading transfers...</p>
                                </div>
                            </div>
                        </div>
                    </div>
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
        <div class='modal-dialog modal-dialog-centered'>
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
                                <input type="file" class="form-control" name="part_image" id="edit_part_image"
                                    accept="image/*">
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
        aria-hidden='true' style="padding:0!important;">
        <div class="modal-dialog" style="width:100vw;max-width:100vw;height:100vh;min-height:100vh;margin:0;padding:0;">
            <form id='sellPartsOutForm' class='modal-content border-0'
                style="width:100%;height:100vh;min-height:100vh;border-radius:0;display:flex;flex-direction:column;overflow:hidden;">
                <!-- HEADER -->
                <div class="modal-header bg-dark text-white py-3">
                    <h5 class="modal-title fw-bold" id='sellPartsOutModalLabel'>
                        <i class="bi bi-cart-plus me-2"></i>Record New Sale
                    </h5>
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                </div>

                <!-- BODY -->
                <div class="modal-body p-4 bg-light">
                    <div class="row g-4">

                        <!-- LEFT: Customer & Sale Details -->
                        <div class="col-lg-3 d-flex flex-column" style="position: relative; z-index: 1002 !important;">

                            <!-- Sale Info Card -->
                            <div class="card border-0 shadow-sm rounded-4 mb-3"
                                style="border-top: 5px solid #198754 !important; position: relative; z-index: 1005 !important; overflow: visible !important;">
                                <div class="card-body p-4">
                                    <h6 class="fw-bold mb-3 text-muted text-uppercase small">
                                        <i class="bi bi-file-earmark-text me-2"></i>Step 1: Sale Details
                                    </h6>
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for='out_or_number' class='small fw-bold text-muted'>Sales Invoice
                                                No. *</label>
                                            <input type='text' class='form-control fw-bold border-success'
                                                id='out_or_number' required autocomplete="off" placeholder="Required">
                                            <div id="or_availability_feedback" class="small mt-1"></div>
                                        </div>
                                        <div class="col-12 position-relative"
                                            style="z-index: 1006 !important; overflow: visible !important;">
                                            <label for='out_customer_name' class='small fw-bold text-muted'>Customer
                                                Name *</label>
                                            <input type='text' class='form-control' id='out_customer_name' required
                                                autocomplete="off" placeholder="Type to search customer...">
                                            <div id="saleCustomerSearchResults"
                                                class="list-group border rounded shadow-sm mt-1"
                                                style="max-height: 200px; overflow-y: auto; position: absolute; width: calc(100% - 1.5rem); z-index: 99999 !important; display: none; background: white;">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for='out_date' class='small fw-bold text-muted'>Date of Sale</label>
                                            <input type='date' class='form-control' id='out_date' required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for='out_transaction_type' class='small fw-bold text-muted'>Payment
                                                Type</label>
                                            <select class='form-select' id='out_transaction_type' required>
                                                <option value='cash' selected>Cash Sales</option>
                                                <option value='charge'>Charge Sales</option>
                                                <option value='pdc'>Charge w/ PDC</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sales Force Card -->
                            <div class="card border-0 shadow-sm rounded-4 mt-auto mb-3 mb-lg-0"
                                style="border-top: 5px solid #198754 !important; position: relative; z-index: 1004 !important; overflow: visible !important;">
                                <div class="card-body p-4">
                                    <h6 class="fw-bold mb-3 text-muted text-uppercase small">
                                        <i class="bi bi-person-badge me-2"></i>Step 2: Sales Force
                                        <span class="fw-normal text-muted">(Optional)</span>
                                    </h6>
                                    <div class="position-relative"
                                        style="z-index: 9999 !important; overflow: visible !important;">
                                        <div class="input-group">
                                            <span class="input-group-text bg-white border-end-0">
                                                <i class="bi bi-person text-success"></i>
                                            </span>
                                            <input type="text" id="out_sales_force" class="form-control border-start-0"
                                                placeholder="Type to search employee..." autocomplete="off">
                                        </div>
                                        <div id="salesForceSearchResults"
                                            class="list-group border rounded shadow-sm mt-1"
                                            style="max-height: 180px; overflow-y: auto; position: absolute; width: 100%; z-index: 99999 !important; display: none; background: white;">
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- RIGHT: Part Search + Cart -->
                        <div class="col-lg-9" style="position: relative; z-index: 1001 !important;">

                            <!-- Part Search Card -->
                            <div class="card border-0 shadow-sm rounded-4 mb-3"
                                style="padding: 24px; border-top: 5px solid #198754 !important; position: relative; z-index: 1003 !important; overflow: visible !important;">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0 fw-bold text-muted text-uppercase small">
                                        <i class="bi bi-upc-scan me-2"></i>Step 3: Scan / Search Part
                                    </h6>
                                </div>
                                <div class="position-relative"
                                    style="z-index: 9999 !important; overflow: visible !important;">
                                    <i class="bi bi-search"
                                        style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #004d40; font-size: 1.2rem; z-index: 5;"></i>
                                    <input type="text" id="salePartSearchInput" class="form-control"
                                        style="padding-left: 45px; height: 50px; border-radius: 8px; border: 2px solid #eee;"
                                        placeholder="Enter Part No. or Part Name to add items..." autocomplete="off">
                                    <div id="salePartSearchResults" class="list-group border rounded shadow"
                                        style="position: absolute; top: 100%; left: 0; right: 0; max-height: 250px; overflow-y: auto; z-index: 99999 !important; display: none; background: white;">
                                    </div>
                                </div>
                            </div>

                            <!-- Cart Table Card -->
                            <div class="card border-0 shadow-sm rounded-4 overflow-hidden"
                                style="position: relative; z-index: 1;">
                                <div
                                    class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 fw-bold text-muted text-uppercase small">
                                        <i class="bi bi-list-check me-2"></i>Sale Items
                                    </h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive" style="max-height: 370px;">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="bg-dark text-white">
                                                <tr>
                                                    <th class="ps-4">Item</th>
                                                    <th class="text-center">Qty</th>
                                                    <th class="text-end">Unit Price</th>
                                                    <th class="text-end">Subtotal</th>
                                                    <th class="text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="partsForSaleList">
                                                <tr id="emptySaleListRow">
                                                    <td colspan="5" class="text-center text-muted py-5">Cart is empty
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <!-- Grand Total Footer -->
                                <div class="card-footer bg-white py-3 px-4">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted fw-semibold">Discount (₱)</span>
                                        <div style="width: 150px;">
                                            <input type="number" id="pos-discount" class="form-control text-end" value="0.00" step="0.01" min="0">
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center border-top pt-2">
                                        <span class="text-muted fw-semibold">Grand Total</span>
                                        <span id="pos-grand-total" class="fw-bold fs-4 text-success">₱0.00</span>
                                    </div>
                                    <div id="credit_limit_error" class="alert alert-danger mt-2 py-2 small d-none">
                                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                        <span id="credit_limit_msg"></span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- FOOTER -->
                <div class='modal-footer bg-white border-top'>
                    <button type='button' class='btn btn-premium-cancel' data-bs-dismiss='modal'>Cancel</button>
                    <button type='submit' class='btn btn-success px-5 py-2 fw-bold shadow-sm'
                        style="border-radius:12px;">
                        <i class="bi bi-check-circle me-2"></i>Confirm Sale
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class='modal fade' id='pdcDetailsModal' tabindex='-1' aria-labelledby='pdcDetailsModalLabel'
        aria-hidden='true'>
        <div class='modal-dialog modal-dialog-centered'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-white'>
                    <h5 class='modal-title' id='pdcDetailsModalLabel'><i class="bi bi-wallet2 me-2"></i>PDC Payment
                        Details</h5>
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                </div>
                <div class='modal-body p-4'>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class='form-label fw-bold text-primary'>Bank Name</label>
                            <input type='text' class='form-control border-primary' name='pdc_bank'
                                placeholder="e.g. BDO, Metrobank" required>
                        </div>
                        <div class="col-md-6">
                            <label class='form-label fw-bold text-primary'>Check No.</label>
                            <input type='text' class='form-control border-primary' name='pdc_check_no'
                                placeholder="00012345" required>
                        </div>
                        <div class="col-md-6">
                            <label class='form-label fw-bold text-primary'>Maturity Date</label>
                            <input type='date' class='form-control border-primary' name='pdc_maturity_date' required>
                        </div>
                        <div class="col-md-6">
                            <label class='form-label fw-bold text-primary'>Amount</label>
                            <div class="input-group">
                                <span class="input-group-text border-primary text-primary">₱</span>
                                <input type='number' step="0.01" class='form-control border-primary' name='pdc_amount'
                                    placeholder="0.00" required readonly>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class='form-label fw-bold text-primary'>Remarks (Optional)</label>
                            <textarea class='form-control border-primary' name='pdc_remarks' rows="2"
                                placeholder="Notes..."></textarea>
                        </div>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                    <button type='button' class='btn btn-primary' id='confirmPdcDetailsBtn'><i
                            class="bi bi-check-circle me-2"></i>Confirm PDC Details</button>
                </div>
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

    <!-- SALES RECEIPT MODAL -->
    <div class="modal fade" id="receiptModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white border-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-receipt-cutoff me-2"></i>Sales Receipt</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <div id="receiptContent" class="bg-white p-4 shadow-sm rounded border">
                        <!-- Receipt content will be populated via JS -->
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-secondary px-4 fw-bold" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success px-4 fw-bold" id="btnPrintReceipt">
                        <i class="bi bi-printer me-2"></i>Print Receipt
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class='modal fade' id='recordPaymentModal' tabindex='-1' aria-labelledby='recordPaymentModalLabel'
        aria-hidden='true'>
        <div class='modal-dialog modal-dialog-centered' style="max-width: 480px;">
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
                    <div class='modal-footer'>
                        <button type='button' class='btn btn-premium-cancel' data-bs-dismiss='modal'>Close</button>
                        <button type='submit' class='btn btn-premium-save text-white'>
                            <i class="text-white bi bi-check-lg me-2"></i>Record Payment
                        </button>
                    </div>
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
        <div class='modal-dialog modal-xl modal-dialog-centered'>
            <form id='editSaleForm' class='modal-content'>
                    <div class='modal-header bg-dark text-white'>
                        <h5 class='modal-title'><i class="bi bi-pencil-square me-2"></i>REVISE SALES TRANSACTION</h5>
                        <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                    </div>
                    <div class='modal-body p-4'>
                        <!-- Meta Info Row -->
                        <div class="row g-3 mb-4">
                            <input type="hidden" id="edit_sale_original_or" name="original_or">
                            <input type="hidden" id="edit_sale_original_branch" name="original_branch">

                            <div class="col-md-3">
                                <label class='form-label fw-bold'>SI # / OR #</label>
                                <input type='text' class='form-control fw-bold border-success' id='edit_sale_or'
                                    name="new_or_number" required>
                                <small class="text-muted">Enter new number to change SI#</small>
                            </div>
                            <div class="col-md-3">
                                <label class='form-label fw-bold'>Customer Name</label>
                                <input type='text' class='form-control fw-bold' id='edit_sale_customer'
                                    name="customer_name" required>
                            </div>
                            <div class="col-md-2">
                                <label class='form-label fw-bold'>Sale Date</label>
                                <input type='date' class='form-control' id='edit_sale_date' name="sale_date" required>
                            </div>
                            <div class="col-md-2">
                                <label class='form-label fw-bold'>Payment Type</label>
                                <select class='form-select fw-bold' id='edit_sale_type' name="transaction_type">
                                    <option value="cash">CASH</option>
                                    <option value="charge">CHARGE (A/R)</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class='form-label fw-bold'>Branch</label>
                                <select class='form-select' id='edit_sale_branch' name="from_location" readonly>
                                    <!-- Populated via JS -->
                                </select>
                            </div>
                        </div>

                        <!-- Sales Force Row -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-6 position-relative">
                                <label class="form-label fw-bold">Sales Force</label>
                                <input type="text" class="form-control" id="edit_sale_sales_force" name="sales_force">
                                <div id="editSaleForceResults" class="list-group border rounded shadow-sm mt-1 w-100" style="position: absolute; display: none; z-index: 1056; background: white; max-height: 200px; overflow-y: auto;"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-danger">Reason for Revision (REQUIRED)</label>
                                <input type="text" class="form-control border-danger" id="edit_sale_reason"
                                    name="reason" placeholder="e.g. Corrected Item, Added Discount..." required>
                            </div>
                        </div>

                        <hr>

                        <!-- Items Table Header -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0 text-dark-green"><i class="bi bi-box-seam me-2"></i>SALES ITEMS</h6>
                            <div class="position-relative" style="width: 400px;">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-success"><i
                                            class="bi bi-search"></i></span>
                                    <input type="text" id="editSaleItemSearch" class="form-control border-success"
                                        placeholder="Search Part Number to Add...">
                                </div>
                                <div id="editSaleItemResults" class="list-group border rounded shadow-sm mt-1 w-100"
                                    style="position: absolute; display: none; z-index: 1056; background: white; max-height: 200px; overflow-y: auto;">
                                </div>
                            </div>
                        </div>

                        <!-- Items Table -->
                        <div class="table-responsive border rounded bg-light" style="max-height: 350px;">
                            <table class="table table-sm table-hover align-middle mb-0" id="editSaleItemsTable">
                                <thead class="bg-white sticky-top">
                                    <tr class="small text-uppercase text-muted">
                                        <th class="ps-3 py-2">Part No / Description</th>
                                        <th class="text-center py-2" style="width: 100px;">Quantity</th>
                                        <th class="text-end py-2" style="width: 150px;">Unit Price</th>
                                        <th class="text-end py-2" style="width: 150px;">Subtotal</th>
                                        <th class="text-center py-2" style="width: 60px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="editSaleItemsBody">
                                    <!-- Populated via JS -->
                                </tbody>
                                <tfoot class="bg-white fw-bold">
                                    <tr>
                                        <td colspan="3" class="text-end py-3">TOTAL SALE AMOUNT</td>
                                        <td class="text-end py-3 text-primary fs-5" id="edit_sale_total_display">₱0.00
                                        </td>
                                        <input type="hidden" name="total_amount" id="edit_sale_total_input">
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class='modal-footer bg-light border-0'>
                        <button type='button' class='btn btn-light fw-bold px-4' data-bs-dismiss='modal'>Cancel</button>
                        <button type='submit' class='btn btn-primary fw-bold px-4'><i
                                class="bi bi-check2-circle me-1"></i>UPDATE TRANSACTION</button>
                    </div>
            </form>
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
    <div class='modal fade' id='viewIncomingTransferModal' tabindex='-1'>
        <div class='modal-dialog modal-lg'>
            <div class='modal-content'>
                <div class='modal-header'>
                    <h5 class='modal-title'>Incoming Transfer Details</h5><button type='button' class='btn-close'
                        data-bs-dismiss='modal'></button>
                </div>
                <div class='modal-body' id='incomingTransferDetailsBody'></div>
                <div class='modal-footer'><button type='button' class='btn btn-secondary'
                        data-bs-dismiss='modal'>Cancel</button><button type='button' class='btn btn-success'
                        id="confirmReceiveBtn">Confirm and Receive Items</button></div>
            </div>
        </div>
    </div>

    <div class='modal fade' id='successModal' tabindex='-1'>
        <div class='modal-dialog modal-dialog-centered'>
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
        <div class='modal-dialog modal-dialog-centered'>
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

    <div class='modal fade' id='inventoryBatchPreviewModal' tabindex='-1' aria-hidden='true'>
        <div class='modal-dialog modal-xl'>
            <div class='modal-content'>
                <div class='modal-header bg-dark text-white'>
                    <h5 class='modal-title text-white'><i class="bi bi-eye me-2"></i>Print/Export Preview</h5>
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                </div>
                <div class='modal-body p-4'>
                    <!-- PRINT HEADER (Hidden on Screen) -->
                    <div class="report-header-print d-none d-print-block">
                        <div class="company-name"><?php echo htmlspecialchars($reportHeaderTitle); ?></div>
                        <div class="system-name">Spareparts Management System</div>
                        <div class="report-title-container" style="margin-top: 15px;">
                            <div class="report-title">INVENTORY ITEM REPORT</div>
                            <div class="report-timestamp">Generated on: <?php echo date('F d, Y h:i A'); ?></div>
                        </div>
                    </div>
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
        <div class='modal-dialog modal-dialog-centered'>
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
                            <input type="month" class="form-control border-0 bg-light fw-bold" id="inv_report_month">
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
                            <button type="button" class="btn btn-primary btn-lg fw-bold" id="btnGenerateTransferReport">
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
                        <input type="month" class="form-control border-0 bg-light fw-bold" id="payment_report_month">
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
                            <h4 class="fw-bold mb-0"><?php echo htmlspecialchars($reportHeaderTitle); ?></h4>
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

    <div class='modal fade' id='addCustomerModal' tabindex='-1'>
        <div class='modal-dialog modal-dialog-centered' style="max-width: 450px;">
            <form id='addCustomerForm' class='modal-content border-0'>
                <div class='modal-header bg-primary text-white'>
                    <h5 class='modal-title text-white'><i class="bi bi-person-plus-fill me-2"></i>Add New Customer</h5>
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                </div>
                <div class='modal-body p-4'>
                    <div class="mb-3">
                        <label class='form-label'>Customer Name <span class="text-danger">*</span></label>
                        <input type='text' class='form-control' id='cust_name' name='name' required>
                    </div>
                    <div class="mb-3">
                        <label class='form-label'>Contact Number</label>
                        <input type='text' class='form-control' id='cust_contact' name='contact_no'>
                    </div>
                    <div class="mb-3">
                        <label class='form-label'>Address</label>
                        <textarea class='form-control' id='cust_address' name='address' rows='1'
                            placeholder="Full address..."></textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class='form-label'>Rank Level</label>
                            <input type='text' class='form-control' id='cust_rank' name='rank_level'
                                placeholder="e.g. Bronze, Silver, Gold, VIP" value="Silver">
                        </div>
                        <div class="col-md-6">
                            <label class='form-label'>Term (Days)</label>
                            <input type='number' class='form-control' id='cust_term' name='term' value="0" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class='form-label'>Credit Limit</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type='number' step="0.01" class='form-control' id='cust_limit'
                                    name='credit_limit' value="0.00">
                            </div>
                        </div>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button type='button' class='btn btn-premium-cancel' data-bs-dismiss='modal'>Cancel</button>
                    <button type='submit' class='btn btn-premium-save'>Save Customer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Customer Modal -->
    <div class='modal fade' id='editCustomerModal' tabindex='-1'>
        <div class='modal-dialog modal-dialog-centered' style="max-width: 450px;">
            <form id='editCustomerForm' class='modal-content border-0'>
                <input type="hidden" id="edit_cust_id" name="id">
                <div class='modal-header bg-success text-white'>
                    <h5 class='modal-title'><i class="bi bi-pencil-square me-2"></i>Edit Customer Info</h5>
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                </div>
                <div class='modal-body p-4'>
                    <div class="mb-3">
                        <label class='form-label'>Customer Name <span class="text-danger">*</span></label>
                        <input type='text' class='form-control' id='edit_cust_name' name='name' required>
                    </div>
                    <div class="mb-3">
                        <label class='form-label'>Contact Number</label>
                        <input type='text' class='form-control' id='edit_cust_contact' name='contact_no'>
                    </div>
                    <div class="mb-3">
                        <label class='form-label'>Address</label>
                        <textarea class='form-control' id='edit_cust_address' name='address' rows='1'></textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class='form-label'>Rank Level</label>
                            <input type='text' class='form-control' id='edit_cust_rank' name='rank_level'
                                placeholder="e.g. Bronze, Silver, Gold, VIP">
                        </div>
                        <div class="col-md-6">
                            <label class='form-label'>Term (Days)</label>
                            <input type='number' class='form-control' id='edit_cust_term' name='term' min="0">
                        </div>
                        <div class="col-md-6">
                            <label class='form-label'>Credit Limit</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type='number' step="0.01" class='form-control' id='edit_cust_limit'
                                    name='credit_limit'>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button type='button' class='btn btn-premium-cancel' data-bs-dismiss='modal'>Cancel</button>
                    <button type='submit' class='btn btn-premium-save bg-success text-white'>Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Aging View / Customer Ledger Modal -->
    <div class="modal fade" id="customerLedgerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-success text-white py-3">
                    <h5 class="modal-title fw-bold text-white"><i class="bi bi-person-lines-fill me-2"></i>Customer
                        Transaction History</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" id="ledgerModalBody">
                    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom no-print">
                        <div>
                            <h4 class="fw-bold mb-0 text-dark" id="ledgerCustomerName">Customer Name</h4>
                            <p class="text-muted small mb-0" id="ledgerPeriods">Statement for current period</p>
                        </div>
                        <div class="text-end">
                            <button class="btn btn-primary text-white" onclick="printLedger()">
                                <i class="bi bi-printer me-2 "></i>Print Statement
                            </button>
                        </div>
                    </div>

                    <!-- Standardized Report Header (Print only) -->
                    <div class="report-header-print d-none d-print-block">
                        <div class="company-name"><?php echo htmlspecialchars($reportHeaderTitle); ?></div>
                        <div class="system-name">Spareparts Management System</div>
                        <div class="report-title-container" style="margin-top: 15px;">
                            <div class="report-title">STATEMENT OF ACCOUNT</div>
                            <div class="report-timestamp" id="printLedgerCriteria"></div>
                        </div>
                    </div>

                    <!-- Professional Header Info (Visible on screen and print) -->
                    <div class="row align-items-center mb-4 ledger-meta-box">
                        <div class="col-7">
                            <div class="print-clear-box p-3 border rounded-3 bg-light shadow-sm">
                                <p class="small text-muted mb-1 text-uppercase fw-bold"
                                    style="letter-spacing: 0.5px; font-size: 0.7rem;">
                                    Customer Information:</p>
                                <h4 class="fw-bold mb-1 text-dark text-uppercase" id="printLedgerCustomerName">Customer
                                    Name</h4>
                                <p class="mb-1 small text-secondary" id="printLedgerAddress"></p>
                                <p class="small mb-0 text-secondary" id="printLedgerContact"></p>
                            </div>
                        </div>
                        <div class="col-5">
                            <div class="print-clear-box p-3 border rounded-3 bg-light shadow-sm h-100">
                                <div class="row g-0">
                                    <div class="col-6">
                                        <p class="small text-muted mb-0 text-uppercase fw-bold"
                                            style="letter-spacing: 0.5px; font-size: 0.7rem;">Rank Level</p>
                                        <p class="fw-bold mb-0 text-primary" id="printLedgerRank">-</p>
                                    </div>
                                    <div class="col-6 text-end">
                                        <p class="small text-muted mb-0 text-uppercase fw-bold"
                                            style="letter-spacing: 0.5px; font-size: 0.7rem;">Credit Limit</p>
                                        <p class="fw-bold mb-0 text-danger" id="printLedgerLimit">₱0.00</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="table-responsive rounded border print-no-border">
                        <table class="table table-hover align-middle mb-0" id="ledgerModalTable">
                            <thead class="bg-light fw-bold">
                                <tr class="text-uppercase small">
                                    <th class="ps-3">Date</th>
                                    <th>Reference #</th>
                                    <th>Transaction Info</th>
                                    <th class="text-end">Debit (Charge)</th>
                                    <th class="text-end">Credit (Payment)</th>
                                    <th class="text-end pe-3">Running Balance</th>
                                </tr>
                            </thead>
                            <tbody id="ledgerModalTbody">
                                <!-- Data here -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Professional Print Footer / Signatories -->
                    <div class="d-none d-print-block mt-3">
                        <div class="row g-3 align-items-end">
                            <div class="col-4 text-center">
                                <div class="border-bottom border-dark mb-2" style="height: 35px;"></div>
                                <p class="small fw-bold mb-0 text-uppercase" style="font-size: 0.7rem;">Prepared By:</p>
                                <p class="small text-muted" style="font-size: 0.65rem;">Authorized Personnel</p>
                            </div>
                            <div class="col-4 text-center">
                                <div class="border-bottom border-dark mb-2" style="height: 35px;"></div>
                                <p class="small fw-bold mb-0 text-uppercase" style="font-size: 0.7rem;">Noted / Received
                                    By:</p>
                                <p class="small text-muted" style="font-size: 0.65rem;">Customer / Representative</p>
                            </div>
                            <div class="col-4 text-end small">
                                <div class="p-2 border rounded-3 bg-light-subtle">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted fw-bold">DATE PREPARED:</span>
                                        <span id="printLedgerDateFooter"></span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted fw-bold">TIME:</span>
                                        <span id="printLedgerTimeFooter"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 py-2 border-top text-center">
                            <p class="small text-muted mb-0" style="font-size: 0.7rem; font-style: italic;">
                                This is a system-generated statement. Please settle outstanding balances to maintain
                                your good credit standing.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 no-print">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src='https://code.jquery.com/jquery-3.6.0.min.js'></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>

    <script>
        window.canDelete = <?php echo json_encode($canDelete); ?>;
        window.currentBranch = '<?php echo htmlspecialchars($currentBranch); ?>';
    </script>
    <script src="../js/spareparts_stock_card.js?v=<?php echo time(); ?>"></script>
    <script src='../js/spareparts_inventory.js?v=<?php echo time(); ?>'></script>
    <script src="../js/spareparts_dashboard.js?v=<?php echo time(); ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof updatePendingTransfers === 'function') {
                updatePendingTransfers();
                setInterval(updatePendingTransfers, 30000);
            }
        });
    </script>
    <script src='../js/sales_spareparts.js?v=<?php echo time(); ?>'></script>

    <div class="modal fade" id="paymentsAgingModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-info text-dark">
                    <h5 class="modal-title fw-bold"><i class="bi bi-clock-history me-2"></i>Payments Aging View
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light">
                    <div class="row mb-3 align-items-end aging-branch-filter-container">
                        <div class="col-md-3">
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
                    <!-- Removed Sales Batch Action Bar as checkboxes are removed in Admin -->
                    <div class='table-responsive rounded shadow-sm bg-white'>
                        <table class='table table-hover align-middle mb-0' id='paymentsAgingTable'>
                            <thead class="bg-light border-bottom border-2">
                                <tr>
                                    <th style="width: 40px;"></th>
                                    <th>Branch</th>
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
    <!-- MANAGE SALES FORCE MODAL -->
    <div class='modal fade' id='manageSalesForceModal' tabindex='-1' aria-hidden='true'>
        <div class='modal-dialog modal-md modal-dialog-centered'>
            <div class='modal-content border-0 shadow-lg'>
                <div class='modal-header bg-success text-white border-0'>
                    <h5 class='modal-title fw-bold text-white'><i class="bi bi-person-badge me-2"></i>Manage Employees
                    </h5>
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                </div>
                <div class='modal-body p-4'>
                    <!-- Add new employee form -->
                    <div class="card border-0 bg-light rounded-3 mb-4">
                        <div class="card-body p-3">
                            <h6 class="fw-bold mb-3 text-dark"><i class="bi bi-person-plus me-2 text-success"></i>Add
                                Employee</h6>
                            <form id="addSalesForceForm">
                                <div class="row g-2">
                                    <div class="col-md-7">
                                        <input type="text" id="sf_employee_name" class="form-control"
                                            placeholder="Employee full name..." required autocomplete="off">
                                    </div>
                                    <div class="col-md-5">
                                        <input type="text" id="sf_position" class="form-control"
                                            placeholder="Position..." autocomplete="off">
                                    </div>
                                    <div class="col-12 text-end mt-2">
                                        <button type="submit" class="btn btn-success fw-bold px-4">
                                            <i class="bi bi-plus-lg me-1"></i>Add Staff
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <!-- Employee List -->
                    <h6 class="fw-bold mb-3 text-dark"><i class="bi bi-people me-2 text-success"></i>Your Branch
                        Employees</h6>
                    <div id="salesForceListContainer">
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
                    <h5 class='modal-title fw-bold text-white'><i class="bi bi-pencil-square me-2"></i>Edit Employee
                    </h5>
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
                            <input type="text" id="edit_sf_position" class="form-control"
                                placeholder="e.g. Sales, Mechanic...">
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

    <!-- BULK PRICELIST MODAL -->
    <div class='modal fade' id='bulkPricelistModal' tabindex='-1' aria-hidden='true'>
        <div class='modal-dialog modal-lg modal-dialog-centered'>
            <div class='modal-content border-0 shadow-lg text-dark'>
                <div class='modal-header bg-navy text-white border-0'>
                    <h5 class='modal-title fw-bold'><i class="bi bi-stack me-2"></i>Bulk Set Rank-Based Prices</h5>
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                </div>
                <div class='modal-body p-4'>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">1. SELECT RANK LEVEL</label>
                            <input type="text" class="form-control fw-bold border-primary" id="bulk_rank_level"
                                list="rankListOptions" placeholder="e.g. Gold, Wholesale" required>
                        </div>
                        <div class="col-md-6 position-relative">
                            <label class="form-label small fw-bold">2. ADD PRODUCTS</label>
                            <label class='form-label small fw-bold text-muted text-uppercase mb-1'>Sales Invoice
                                No.</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i
                                        class="bi bi-hash text-muted"></i></span>
                                <input type='text' id='out_or_number' class='form-control border-start-0 fw-bold'
                                    placeholder='Enter SI Number' required>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                <input type="text" id="bulk_part_search" class="form-control"
                                    placeholder="Search by number or name..." autocomplete="off">
                            </div>
                            <div id="bulk_search_results" class="list-group border rounded shadow-sm mt-1 w-100"
                                style="position: absolute; display: none; z-index: 1060; background: white; max-height: 200px; overflow-y: auto;">
                            </div>
                        </div>
                    </div>

                    <label class="form-label small fw-bold">3. REVIEW & SET PRICES</label>
                    <div class="table-responsive border rounded bg-light" style="max-height: 400px; overflow-y:auto;">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="bg-white sticky-top">
                                <tr>
                                    <th class="ps-3 py-2">Part Number & Description</th>
                                    <th class="py-2 text-end" style="width: 150px;">Default Price</th>
                                    <th class="py-2 text-end" style="width: 180px;">Set Rank Price</th>
                                    <th class="text-center py-2" style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody id="bulkPricelistItems">
                                <tr id="bulk-empty-row">
                                    <td colspan="4" class="text-center text-muted p-4">No products added yet. Use the
                                        search to add items.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class='modal-footer border-0 bg-light'>
                    <span class="me-auto small text-muted"><span id="bulk-count">0</span> items selected</span>
                    <button type='button' class='btn btn-secondary px-4' data-bs-dismiss='modal'>Cancel</button>
                    <button type='button' id="saveBulkPricelistBtn" class="btn btn-primary px-4 fw-bold">Save All
                        Prices</button>
                </div>
            </div>
        </div>
    </div>

    <!-- SAVE PRICELIST MODAL -->
    <div class='modal fade' id='savePricelistModal' tabindex='-1' aria-hidden='true'>
        <div class='modal-dialog modal-md modal-dialog-centered'>
            <div class='modal-content border-0 shadow-lg'>
                <div class='modal-header bg-dark text-white border-0'>
                    <h5 class='modal-title fw-bold'><i class="bi bi-tag-fill me-2"></i>Set Rank-Based Price</h5>
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                </div>
                <form id="savePricelistForm">
                    <div class='modal-body p-4'>
                        <div class="mb-3 position-relative">
                            <label class="form-label small fw-bold">SEARCH PART</label>
                            <input type="text" id="pl_part_search" class="form-control"
                                placeholder="Type part no or description..." required autocomplete="off">
                            <input type="hidden" id="pl_part_no" name="part_no" required>
                            <div id="pl_search_results" class="list-group border rounded shadow-sm mt-1 w-100"
                                style="position: absolute; display: none; z-index: 1056; background: white; max-height: 200px; overflow-y: auto;">
                            </div>
                        </div>
                        <div id="pl_selected_part_info"
                            class="mb-3 p-2 bg-light border-start border-4 border-success rounded d-none">
                            <div class="fw-bold" id="pl_sel_part_no"></div>
                            <div class="small text-muted" id="pl_sel_description"></div>
                            <div class="small fw-bold text-primary mt-1">Default Price: <span id="pl_sel_price"></span>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label class="form-label small fw-bold">RANK LEVEL</label>
                                <input type="text" class="form-control" id="pl_rank_level" name="rank_level"
                                    list="rankListOptions" placeholder="e.g. Bronze, Silver, Gold" required>
                                <datalist id="rankListOptions">
                                    <option value="Bronze">
                                    <option value="Silver">
                                    <option value="Gold">
                                    <option value="Platinum">
                                    <option value="VIP">
                                    <option value="Wholesale">
                                </datalist>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-bold">RANK PRICE</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" step="0.01" class="form-control fw-bold" name="price" required
                                        min="0.01">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class='modal-footer border-0 bg-light'>
                        <button type='button' class='btn btn-secondary px-4' data-bs-dismiss='modal'>Cancel</button>
                        <button type='submit' class='btn btn-primary px-4 fw-bold'>Save Price</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- RECORD RETURN (CM) MODAL -->
    <div class="modal fade" id="recordReturnModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-danger text-white border-0">
                    <h5 class="modal-title fw-bold text-white"><i class="bi bi-arrow-return-left me-2"></i>Record Return
                        / Credit Memo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Step 1: Search Customer -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-uppercase small">Customer Name</label>
                        <div class="position-relative">
                            <input type="text" class="form-control fw-bold" id="returnCustomerSearch"
                                placeholder="Type customer name to search..." autocomplete="off">
                            <div id="returnCustomerResults" class="list-group border rounded shadow-sm mt-1 w-100"
                                style="position: absolute; display: none; z-index: 1056; background: white; max-height: 200px; overflow-y: auto;">
                            </div>
                        </div>
                        <input type="hidden" id="returnCustomerName">
                    </div>

                    <!-- Step 2: Customer Sales Items -->
                    <div id="returnSalesContainer" class="d-none">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold mb-0"><i class="bi bi-bag-check me-1"></i>Sales for: <span
                                    id="returnCustomerLabel" class="text-primary"></span></h6>
                            <span class="badge bg-secondary" id="returnItemCount">0 items</span>
                        </div>
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="bg-light sticky-top">
                                    <tr class="small text-uppercase text-muted">
                                        <th style="width: 40px;"><input type="checkbox" class="form-check-input"
                                                id="selectAllReturnItems"></th>
                                        <th>Date</th>
                                        <th>SI #</th>
                                        <th>Part No</th>
                                        <th>Description</th>
                                        <th class="text-center">Sold Qty</th>
                                        <th class="text-end">Price</th>
                                        <th class="text-center" style="width: 100px;">Return Qty</th>
                                    </tr>
                                </thead>
                                <tbody id="returnSalesBody"></tbody>
                            </table>
                        </div>

                        <hr>
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase">Return Date</label>
                                <input type="date" class="form-control" id="returnDate" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase">Remarks</label>
                                <input type="text" class="form-control" id="returnRemarks"
                                    placeholder="e.g. Defective, Wrong item..." value="Returned to inventory">
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="bg-light p-3 rounded border">
                                    <div class="small text-muted text-uppercase fw-bold">Total Credit Amount</div>
                                    <div class="fs-4 fw-bold text-danger" id="returnTotalCredit">₱0.00</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty state -->
                    <div id="returnEmptyState" class="text-center py-5 text-muted">
                        <i class="bi bi-search fs-1 d-block mb-3 opacity-50"></i>
                        <p>Search for a customer name above to see their purchased items.</p>
                    </div>
                </div>
                <div class='modal-footer border-0 p-4 pt-0'>
                    <button type="button" class="btn btn-secondary px-4 fw-bold" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-danger px-4 fw-bold" id="confirmReturnBtn">Confirm
                        Return</button>
                </div>
            </div>
        </div>
    </div>

    <!-- STOCK CARD MODAL -->
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
                                <span id="stockCardPartNo" class="badge bg-dark px-3 py-2"
                                    style="font-size: 0.85rem; letter-spacing: 0.5px;">PART-NO-PLACEHOLDER</span>
                                <span id="stockCardBrand"
                                    class="text-muted small fw-bold text-uppercase border-start ps-2">BRAND NAME</span>
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
                                        <div id="stockCardImage"
                                            class="rounded-4 border bg-white mx-auto d-flex align-items-center justify-content-center overflow-hidden shadow-sm mb-3"
                                            style="width: 150px; height: 150px; background: #f1f5f9;">
                                            <i class="bi bi-image text-muted opacity-25" style="font-size: 3rem;"></i>
                                        </div>
                                        <h5 id="stockCardDescription" class="fw-bold text-dark mb-1">PART NAME /
                                            DESCRIPTION</h5>
                                        <div class="badge bg-light text-muted border px-3 py-2 mt-2"
                                            style="font-size: 0.8rem;">
                                            <i class="bi bi-geo-alt me-1"></i> STORAGE: <span
                                                id="stockCardBin">N/A</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-2">
                                    <div class="col-12">
                                        <div
                                            class="p-3 bg-white border-0 shadow-sm rounded-4 d-flex justify-content-between align-items-center mb-1">
                                            <div>
                                                <div class="small text-muted fw-bold text-uppercase mb-0"
                                                    style="font-size: 0.65rem;">Currently In Stock</div>
                                                <div class="fw-bold fs-4 text-dark"><span id="stockCardQty">0</span>
                                                    <span class="fs-6 fw-normal text-muted">pcs</span>
                                                </div>
                                            </div>
                                            <div class="p-2 rounded-circle bg-success bg-opacity-10">
                                                <i class="bi bi-box-fill text-success fs-5"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="p-3 bg-white border-0 shadow-sm rounded-4">
                                            <div class="small text-muted fw-bold text-uppercase mb-0"
                                                style="font-size: 0.65rem;">Cost</div>
                                            <div id="stockCardCost" class="fw-bold text-dark mb-0"
                                                style="font-size: 0.9rem;">₱0.00</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="p-3 bg-white border-0 shadow-sm rounded-4">
                                            <div class="small text-muted fw-bold text-uppercase mb-0"
                                                style="font-size: 0.65rem;">Selling</div>
                                            <div id="stockCardPrice" class="fw-bold text-success mb-0"
                                                style="font-size: 0.9rem;">₱0.00</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-8">
                                <div class="card border-0 shadow-sm flex-grow-1" style="border-radius: 16px;">
                                    <div class="card-header bg-white border-0 pt-4 px-4">
                                        <nav>
                                            <div class="nav nav-pills gap-2" id="nav-tab" role="tablist">
                                                <button
                                                    class="nav-link active fw-bold px-4 rounded-pill d-flex align-items-center"
                                                    id="nav-move-tab" data-bs-toggle="tab" data-bs-target="#sc-movement"
                                                    type="button" role="tab">
                                                    <i class="bi bi-arrow-left-right me-2"></i> Activity Log (Ins &
                                                    Outs)
                                                </button>
                                                <button
                                                    class="nav-link fw-bold px-4 rounded-pill d-flex align-items-center"
                                                    id="nav-cost-tab" data-bs-toggle="tab" data-bs-target="#sc-history"
                                                    type="button" role="tab">
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
                                                                <th
                                                                    class="ps-4 text-muted fw-bold small text-uppercase border-0 py-3">
                                                                    Date</th>
                                                                <th
                                                                    class="text-muted fw-bold small text-uppercase border-0 py-3">
                                                                    Type of Activity</th>
                                                                <th
                                                                    class="text-center text-muted fw-bold small text-uppercase border-0 py-3">
                                                                    Qty</th>
                                                                <th
                                                                    class="text-muted fw-bold small text-uppercase border-0 py-3">
                                                                    To/From</th>
                                                                <th
                                                                    class="pe-4 text-center text-muted fw-bold small text-uppercase border-0 py-3">
                                                                    Reference #</th>
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
                                                                <th
                                                                    class="ps-4 text-muted fw-bold small text-uppercase border-0 py-3">
                                                                    Receive Date</th>
                                                                <th
                                                                    class="text-muted fw-bold small text-uppercase border-0 py-3">
                                                                    Supplier Source</th>
                                                                <th
                                                                    class="text-end text-muted fw-bold small text-uppercase border-0 py-3">
                                                                    Cost</th>
                                                                <th
                                                                    class="pe-4 text-center text-muted fw-bold small text-uppercase border-0 py-3">
                                                                    Ref Invoice</th>
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
                </div> <!-- End modal-body -->
                <div class="modal-footer bg-white border-0 px-4 pb-4 pt-0">
                    <div class="modal-fs-container d-flex justify-content-end">
                        <button type="button" class="btn btn-dark fw-bold px-5 py-2 rounded-pill shadow-sm"
                            data-bs-dismiss="modal">Close Card</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>

</html>