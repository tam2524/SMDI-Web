<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: ../login.html');
    exit();
}

$username = $_SESSION['username'];
$position = $_SESSION['position'] ?? 'User';
$branch = $_SESSION['user_branch'] ?? 'HEADOFFICE';

// Role-based title
$roleTitle = "Master Reports";
$isAdmin = in_array($position, ['Admin', 'Head', 'itsuperadmin', 'Admin Spareparts', 'Spareparts-Admin', 'Spareparts-Owner']);
$backLink = "admin_dashboard.php";

if ($position === 'Spareparts-Owner') {
    $backLink = "owner_dashboard.php";
}
elseif ($position === 'Spareparts-Warehouse') {
    $backLink = "warehouse_dashboard.php";
}
elseif ($position === 'Spareparts-Sales') {
    $backLink = "sales_dashboard.php";
}
elseif ($position === 'Spareparts-Retail') {
    $backLink = "sales_dashboard.php";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Reports - Spareparts MS</title>
    <link rel="icon" href="../assets/img/smdi_logosmall.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/spareparts_premium.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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

        body {
            font-family: 'Inter', sans-serif;
            background: var(--gray-50);
            color: var(--gray-700);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

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
        .nav-brand-text {
            font-size: 1rem; font-weight: 700; color: var(--white);
            letter-spacing: 0.3px; text-transform: uppercase;
        }
        .btn-back {
            color: white; text-decoration: none; font-size: 0.85rem;
            display: flex; align-items: center; gap: 6px;
            transition: opacity 0.2s;
        }
        .btn-back:hover { opacity: 0.8; color: white; }

        .main-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }

        .report-grid {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 2rem;
            margin-top: 1rem;
        }

        /* Sidebar Sidebar */
        .report-sidebar {
            background: white;
            border-radius: 12px;
            border: 1.5px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
            padding: 1.5rem;
            height: fit-content;
            position: sticky;
            top: 84px;
        }
        .sidebar-title {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--gray-500);
            margin-bottom: 1.25rem;
            display: block;
        }
        .nav-link-custom {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 8px;
            color: var(--gray-700);
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 500;
            transition: all 0.2s;
            margin-bottom: 4px;
        }
        .nav-link-custom:hover {
            background: var(--green-50);
            color: var(--green-800);
        }
        .nav-link-custom.active {
            background: var(--green-800);
            color: white;
        }
        .nav-link-custom i { font-size: 1.1rem; }

        /* Report Form Card */
        .report-card {
            background: white;
            border-radius: 12px;
            border: 1.5px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
            padding: 2rem;
        }
        .report-header {
            margin-bottom: 2rem;
            border-bottom: 1.5px solid var(--gray-100);
            padding-bottom: 1rem;
        }
        .report-title {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--green-900);
            margin-bottom: 4px;
        }
        .report-desc {
            font-size: 0.88rem;
            color: var(--gray-500);
        }

        .form-label {
            font-weight: 600;
            font-size: 0.82rem;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
        }
        .form-control, .form-select {
            border-radius: 8px;
            border: 1.5px solid var(--gray-200);
            padding: 0.6rem 0.8rem;
            font-size: 0.9rem;
            transition: border-color 0.2s;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--green-400);
            box-shadow: 0 0 0 3px rgba(38, 166, 154, 0.1);
        }

        .btn-generate {
            background: var(--green-800);
            border: none;
            color: white;
            padding: 0.8rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .btn-generate:hover {
            background: var(--green-900);
            transform: translateY(-1px);
        }

        .badge-ho { background: var(--green-800); color: white; }

        /* Preview Table Styles */
        .preview-container {
            margin-top: 2rem;
            display: none;
        }
        .preview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .table-responsive {
            border-radius: 12px;
            border: 1px solid var(--gray-200);
            overflow: hidden;
        }
        .table thead th {
            background: var(--gray-100);
            color: var(--gray-700);
            font-weight: 700;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 15px;
            border-bottom: 2px solid var(--gray-200);
        }
        .table tbody td {
            font-size: 0.85rem;
            padding: 12px 15px;
            vertical-align: middle;
        }

        @media (max-width: 992px) {
            .report-grid { grid-template-columns: 1fr; }
            .report-sidebar { position: static; }
        }

        @media print {
            .top-nav, .report-sidebar, #masterReportForm, .preview-header, .report-title, .report-desc, .btn-back, hr, .sidebar-title {
                display: none !important;
            }
            body { background: white !important; padding: 0 !important; }
            .main-container { padding: 0 !important; max-width: 100% !important; margin: 0 !important; }
            .report-content { width: 100% !important; margin: 0 !important; }
            .report-card { border: none !important; box-shadow: none !important; padding: 0 !important; }
            .preview-container { display: block !important; margin-top: 0 !important; }
            .table-responsive { border: none !important; overflow: visible !important; }
            .table { width: 100% !important; border-collapse: collapse !important; }
            .table th, .table td { border: 1px solid #dee2e6 !important; }
            .report-summary-box { border: 1px solid #dee2e6 !important; margin-top: 20px !important; }
            #reportSummaryBox { display: flex !important; justify-content: space-around !important; border: 1px solid #000 !important; padding: 15px !important; }
            .report-title-print { display: block !important; margin-bottom: 20px !important; text-align: center !important; }
        }
        .report-title-print { display: none; }
    </style>
</head>
<body>

    <nav class="top-nav">
        <div class="nav-brand">
            <img src="../assets/img/rcsm_logo.jpg" alt="Logo" height="34" style="border-radius: 6px;">
            <span class="nav-brand-text">Master Central Reports</span>
        </div>
    </nav>

    <div class="main-container">
        
        <div class="report-grid">
            
            <!-- Sidebar -->
            <div class="report-sidebar">
                <span class="sidebar-title">Report Categories</span>
                
                <a href="#" class="nav-link-custom active" data-category="inventory">
                    <i class="bi bi-boxes"></i> Inventory Management
                </a>
                <a href="#" class="nav-link-custom" data-category="sales">
                    <i class="bi bi-receipt"></i> Sales & Revenue
                </a>
                <a href="#" class="nav-link-custom" data-category="payments">
                    <i class="bi bi-cash-coin"></i> Accounts Receivable
                </a>
                <a href="#" class="nav-link-custom" data-category="transfer">
                    <i class="bi bi-truck"></i> Transfers & Locations
                </a>
                
                <hr class="my-3 text-muted">
                
                <span class="sidebar-title">Export Options</span>
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary btn-sm" id="printReportBtn">
                        <i class="bi bi-printer me-2"></i>Print Report
                    </button>
                    <button class="btn btn-outline-success btn-sm export-btn" data-type="excel">
                        <i class="bi bi-file-earmark-spreadsheet me-2"></i>Export to Excel
                    </button>
                    <button class="btn btn-outline-danger btn-sm export-btn" data-type="pdf">
                        <i class="bi bi-file-earmark-pdf me-2"></i>Export to PDF
                    </button>
                </div>
            </div>

            <!-- Content Area -->
            <div class="report-content">
                
                <div class="report-card">
                    <div class="report-header">
                        <div class="d-flex align-items-center gap-3 mb-3">
                             <a href="<?php echo $backLink; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-arrow-left"></i> Back to Dashboard
                            </a>
                            <h2 class="report-title mb-0" id="activeReportTitle">Inventory Management Reports</h2>
                        </div>
                        <div class="report-title-print" style="text-align: center; border-bottom: 3px double #004d40; padding-bottom: 20px; margin-bottom: 30px;">
                            <h2 style="font-weight: 800; color: #004d40; text-transform: uppercase; margin-bottom: 5px; letter-spacing: 1px;">ROXAS CITY SOLID MERCHANDISING</h2>
                            <p style="margin: 0; font-size: 0.9rem; color: #555;">Pueblo de Panay, Lawaan, Roxas City, Capiz</p>
                            <p style="margin: 0; font-size: 0.8rem; color: #777;">Spare Parts Management System | Master Document</p>
                            <div style="margin-top: 15px;">
                                <h4 class="fw-bold" id="printTitle" style="color: #000; display: inline-block; border-bottom: 1px solid #000; padding: 0 20px;"></h4>
                                <p class="small text-muted mt-1" id="printCriteria"></p>
                            </div>
                        </div>
                        <p class="report-desc" id="activeReportDesc">Maintain optimal stock levels and prevent stock-outs.</p>
                    </div>

                    <form id="masterReportForm">
                        <div class="row g-4">
                            
                            <!-- Report Type Selection -->
                            <div class="col-md-6">
                                <label class="form-label text-success text-uppercase small letter-spacing-1">Specific Report</label>
                                <select class="form-select fw-bold" id="report_type" name="report_type" required>
                                    <!-- Options populated by JS -->
                                </select>
                            </div>

                            <!-- Branch Selection (Locked for non-admins) -->
                            <div class="col-md-6">
                                <label class="form-label text-success text-uppercase small letter-spacing-1">Branch filter</label>
                                <select class="form-select fw-bold" id="branch_filter" name="branch" <?php echo !$isAdmin ? 'disabled' : ''; ?>>
                                    <?php if ($isAdmin): ?>
                                        <option value="all">All Branches</option>
                                    <?php
else: ?>
                                        <option value="<?php echo $branch; ?>"><?php echo $branch; ?></option>
                                    <?php
endif; ?>
                                </select>
                            </div>

                            <!-- Time Period -->
                            <div class="col-md-4">
                                <label class="form-label">Period</label>
                                <select class="form-select" id="period" name="period">
                                    <option value="monthly" selected>Monthly</option>
                                    <option value="daily">Daily / As of Date</option>
                                    <option value="custom">Custom Range</option>
                                </select>
                            </div>

                            <!-- Date Picker -->
                            <div class="col-md-4 date-input-wrap" id="month_input_wrap">
                                <label class="form-label">Select Month</label>
                                <input type="month" class="form-control" name="month_value" id="month_value" value="<?php echo date('Y-m'); ?>">
                            </div>

                            <div class="col-md-4 date-input-wrap d-none" id="date_input_wrap">
                                <label class="form-label">Select Date</label>
                                <input type="date" class="form-control" name="date_value" id="date_value" value="<?php echo date('Y-m-d'); ?>">
                            </div>

                            <div class="col-md-8 date-input-wrap d-none" id="custom_input_wrap">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label">Start Date</label>
                                        <input type="date" class="form-control" name="start_date" id="start_date" value="<?php echo date('Y-m-01'); ?>">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">End Date</label>
                                        <input type="date" class="form-control" name="end_date" id="end_date" value="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- Additional Filters -->
                            <div class="col-md-3">
                                <label class="form-label">Search Brand</label>
                                <input type="text" class="form-control" id="brand_search" name="brand" placeholder="e.g. Honda">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Specific Part #</label>
                                <input type="text" class="form-control" id="part_no_search" name="part_no" placeholder="e.g. 123456">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Customer Search</label>
                                <input type="text" class="form-control" id="customer_search" name="customer" placeholder="Search by Customer Name">
                            </div>

                        </div>

                        <div class="mt-5 d-flex justify-content-between align-items-center">
                            <div class="text-muted small">
                                <i class="bi bi-info-circle me-1"></i> Previews are limited to the first 50 records for performance.
                            </div>
                            <button type="submit" class="btn btn-generate">
                                <i class="bi bi-play-circle"></i> Generate Report Preview
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Preview Area -->
                <div class="preview-container" id="previewArea">
                    <div class="preview-header">
                        <h5 class="fw-bold mb-0">Report Preview</h5>
                        <div class="small fw-bold text-success" id="recordsCount">Showing 0 records</div>
                    </div>
                    
                    <div class="table-responsive bg-white shadow-sm">
                        <table class="table table-hover mb-0" id="reportPreviewTable">
                            <thead id="previewThead"></thead>
                            <tbody id="previewTbody"></tbody>
                            <tfoot id="previewTfoot" class="fw-bold bg-light"></tfoot>
                        </table>
                    </div>

                    <div class="mt-4 p-3 bg-white border rounded shadow-sm d-flex justify-content-between" id="reportSummaryBox">
                        <!-- Summary stats here -->
                    </div>
                </div>

            </div>
        </div>

    </div>

    <!-- Modals -->
    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body p-4 text-center">
                    <div id="statusIcon" class="mb-3"></div>
                    <h5 id="statusTitle" class="fw-bold"></h5>
                    <p id="statusMsg" class="text-muted mb-0"></p>
                </div>
                <div class="modal-footer border-0 pt-0 justify-content-center">
                    <button type="button" class="btn btn-primary px-4" data-bs-dismiss="modal">Got it</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>
    
    <script>
        // Pass PHP session info to JS
        window.userBranch = "<?php echo $branch; ?>";
        window.isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;
    </script>
    <script src="../js/master_reports.js"></script>
</body>
</html>
