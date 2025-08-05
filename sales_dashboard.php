<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>SMDI - SALES | The Highest Levels of Service</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">
    
    <link rel="icon" href="assets/img/smdi_logosmall.png" type="image/png">

    <!-- Icon Font Stylesheet -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="lib/lightbox/css/lightbox.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Template Stylesheet -->
    <link href="css/style.css" rel="stylesheet">
    
    <!-- PrintJS -->
    <link rel="stylesheet" href="https://printjs-4de6.kxcdn.com/print.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://printjs-4de6.kxcdn.com/print.min.js"></script>

<style>
        .table-responsive {
            overflow-x: auto;
        }
        
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 20px 0;
}

.pagination .page-item {
    margin: 0 5px;
}

.pagination .page-link {
    padding: 10px 15px;
    border: 1px solid #000f71;
    border-radius: 5px;
    color: #000f71;
    text-decoration: none;
    transition: background-color 0.3s, color 0.3s;
}

.pagination .page-link:hover {
    background-color: #000f71;
    color: white;
}

.pagination .page-item.active .page-link {
    background-color: #000f71;
    color: white;
    border-color: #000f71;
}

.pagination .page-item.disabled .page-link {
    color: #ccc;
    pointer-events: none;
    background-color: white;
    border-color: #ccc;
}

.pagination .page-item.disabled .page-link:hover {
    background-color: white;
    color: #ccc;
}

.no-print {
    display: block;
}

@media print {
    .no-print {
        display: none !important;
    }
}

body {
    padding-top: 70px;
    background-color: #f8f9fa;
}
.card {
    box-shadow: 0 0.15rem 1.75rem 0 rgba(33, 40, 50, 0.15);
}
.card-header {
    font-weight: 500;
}
.card-header .fas {
    margin-right: 8px;
}
.logo {
    height: 40px;
}
  #summaryReportModal .modal-content {
        box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.1);
        border-radius: 0.5rem;
    }
    
    #summaryReportModal .modal-header {
        border-radius: 0.5rem 0.5rem 0 0 !important;
        padding: 1rem 1.5rem;
    }
    
    #summaryReportModal .modal-body {
        padding: 0;
    }
    
    #summaryReportTable {
        font-size: 0.875rem;
    }
    
    #summaryReportTable th {
        background-color: #f8f9fa;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        color: #6c757d;
    }
    
    #summaryReportTable td, #summaryReportTable th {
        vertical-align: middle;
        padding: 0.75rem 1rem;
    }
    
    #summaryReportTable tr:not(:last-child) td {
        border-bottom: 1px solid #eceef1;
    }
    
    .nav-tabs .nav-link {
        font-weight: 500;
        color: #6c757d;
        border: none;
        padding: 0.75rem 1.25rem;
    }
    
    .nav-tabs .nav-link.active {
        color: #000f71;
        border-bottom: 3px solid #000f71;
        background: transparent;
    }
    
    @media (max-width: 991.98px) {
        #summaryReportModal .modal-dialog {
            margin: 0.5rem auto;
        }
        
        #summaryReportTable {
            font-size: 0.8125rem;
        }
        
        .nav-tabs .nav-link {
            padding: 0.5rem;
            font-size: 0.8125rem;
        }
    }
    
    /* Sticky table header */
    .table-responsive {
        position: relative;
    }
    
    .table-responsive thead tr:nth-child(1) th {
        position: sticky;
        top: 0;
        z-index: 10;
        background-color: #f8f9fa;
    }
</style>
</head>

<body>
    <!-- Loading Spinner -->
    <div class="spinner-container" id="loadingSpinner">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <!-- Navbar -->
    <div class="container-fluid fixed-top bg-white">
        <div class="container topbar bg-primary d-none d-lg-block">
            <div class="d-flex justify-content-between">
                <div class="top-info ps-2">
                    <small class="me-3">
                        <i class="fas fa-map-marker-alt me-2 text-primary"></i> 
                        <a href="#" class="text-white">1031, Victoria Building, Roxas Avenue, Roxas City, 5800</a>
                    </small>
                </div>
                <div class="top-link pe-2"></div>
            </div>
        </div>
        <div class="container px-0">
            <nav class="navbar navbar-light bg-white navbar-expand-lg">
                <a href="index.html" class="navbar-brand">
                    <img src="assets/img/smdi_logo.jpg" alt="SMDI Logo" class="logo">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse" 
                        aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarCollapse">
                    <div class="navbar-nav">
                        <a href="staffDashboard.html" class="nav-item nav-link active">Home</a>
                        <a href="api/logout.php" class="nav-item nav-link active">Logout</a>
                    </div>
                </div>
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <main class="container-fluid py-5" style="margin-top: 120px;">
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h1 class="h5 mb-0">Sales Records Management</h1>
            </div>
            <div class="card-body">
                <!-- Action Buttons -->
                <div class="action-buttons mb-4">
                    <button class="btn btn-primary text-white mb-2" data-bs-toggle="modal" data-bs-target="#salesQuotaModal">
                        <i class="fas fa-chart-line me-2"></i>Set Sales Quotas
                    </button>
                    <button class="btn btn-primary text-white mb-2" data-bs-toggle="modal" data-bs-target="#addSaleModal">
                        <i class="fas fa-plus-circle me-2"></i>Add New Sale
                    </button>
                    <button id="deleteSelectedButton" class="btn btn-primary text-white mb-2">
                        <i class="fas fa-trash-alt me-2"></i>Delete Selected
                    </button>
                    <button class="btn btn-primary text-white mb-2" data-bs-toggle="modal" data-bs-target="#summaryReportModal">
                        <i class="fas fa-chart-pie me-2"></i>View Summary Report
                    </button>
                    <button class="btn btn-primary text-white mb-2" data-bs-toggle="modal" data-bs-target="#uploadSalesDataModal">
                        <i class="fas fa-chart-pie me-2"></i>Import
                    </button>
                </div>

                <!-- Search and Sort -->
                <div class="mb-3 search-sort-container d-flex">
                    <input type="text" id="searchInput" class="form-control me-2" placeholder="Search by branch, brand or model..." 
                           aria-label="Search sales records">
                    <div class="dropdown">
                        <button class="btn btn-primary text-white dropdown-toggle" type="button" id="sortDropdown" 
                                data-bs-toggle="dropdown" aria-expanded="false" aria-haspopup="true">
                            <i class="fas fa-sort me-1"></i> Sort by
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="sortDropdown">
                            <li><a class="dropdown-item" href="#" data-sort="date_desc"><i class="fas fa-sort-numeric-down me-2"></i>Date (Newest First)</a></li>
                            <li><a class="dropdown-item" href="#" data-sort="date_asc"><i class="fas fa-sort-numeric-up me-2"></i>Date (Oldest First)</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" data-sort="branch_asc"><i class="fas fa-sort-alpha-down me-2"></i>Branch (A-Z)</a></li>
                            <li><a class="dropdown-item" href="#" data-sort="branch_desc"><i class="fas fa-sort-alpha-up me-2"></i>Branch (Z-A)</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" data-sort="brand_asc"><i class="fas fa-sort-alpha-down me-2"></i>Brand (A-Z)</a></li>
                            <li><a class="dropdown-item" href="#" data-sort="brand_desc"><i class="fas fa-sort-alpha-up me-2"></i>Brand (Z-A)</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Sales Table -->
                <div class="table-responsive">
                    <table id="salesTable" class="table table-striped table-hover" aria-describedby="salesTableDesc">
                        <caption id="salesTableDesc" class="visually-hidden">List of sales records with date, branch, brand, model and quantity information</caption>
                        <thead class="table-light">
                            <tr>
                                <th scope="col" style="width: 40px;">
                                    <input type="checkbox" id="selectAll" aria-label="Select all sales records">
                                </th>
                                <th scope="col">Date</th>
                                <th scope="col">Branch</th>
                                <th scope="col">Brand</th>
                                <th scope="col">Model</th>
                                <th scope="col">Quantity</th>
                                <th scope="col" class="no-print">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="salesTableBody">
                            <!-- Sales records will be loaded here by AJAX -->
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    Loading sales data...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <nav aria-label="Sales records pagination">
                    <ul id="paginationControls" class="pagination">
                        <li id="prevPage" class="page-item disabled">
                            <a class="page-link" href="#" tabindex="-1" aria-disabled="true">
                                <i class="fas fa-chevron-left me-1"></i> Previous
                            </a>
                        </li>
                        <li id="nextPage" class="page-item">
                            <a class="page-link" href="#">
                                Next <i class="fas fa-chevron-right ms-1"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </main>
<!-- Sales Quota Modal -->
<div class="modal fade" id="salesQuotaModal" tabindex="-1" aria-labelledby="salesQuotaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="salesQuotaModalLabel">Sales Quotas Management</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between mb-3">
                    <button id="addQuotaBtn" class="btn btn-primary text-white">Add New Quota</button>
                    <div class="input-group" style="width: 300px;">
                        <input type="text" id="quotaSearchInput" class="form-control" placeholder="Search branches...">
                        <button class="btn btn-outline-secondary" type="button" id="quotaSearchBtn">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Add/Edit Quota Form (initially hidden) -->
                <div id="quotaFormContainer" style="display: none;">
                    <div id="quotaErrorMessage" class="alert alert-danger" style="display: none;"></div>
                    <div id="quotaSuccessMessage" class="alert alert-success" style="display: none;"></div>
                    <form id="salesQuotaForm">
                        <input type="hidden" id="quotaId">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="quotaYear" class="form-label">Year</label>
                                <select class="form-select" id="quotaYear" required>
                                    <!-- Options will be populated by JavaScript -->
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="quotaBranch" class="form-label">Branch</label>
                                <select class="form-select" id="quotaBranch" required>
                                    <!-- Options will be populated from your branch list -->
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="quotaAmount" class="form-label">Quota Amount</label>
                                <input type="number" class="form-control" id="quotaAmount" min="1" required>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="button" id="cancelQuotaBtn" class="btn btn-primary text-white me-2">Cancel</button>
                            <button type="submit" class="btn btn-primary text-white">Save Quota</button>
                        </div>
                    </form>
                    <hr>
                </div>
                
                <h5 class="mt-4">Current Quotas</h5>
                <div class="table-responsive">
                    <table id="quotasTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>Year</th>
                                <th>Branch</th>
                                <th>Quota</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="quotasTableBody">
                            <!-- Quotas will be loaded here by AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="summaryReportModal" tabindex="-1" aria-labelledby="summaryReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-fullscreen-lg-down">
        <div class="modal-content border-0">
            <!-- Modal Header -->
            <div class="modal-header bg-primary text-white">
                <div class="d-flex align-items-center">
                    <i class="fas fa-chart-bar fs-4 me-3"></i>
                    <div>
                        <h5 class="modal-title mb-0 text-white" id="summaryReportModalLabel">Sales Performance Dashboard</h5>
                        <small class="opacity-75">Comprehensive sales analysis and reporting</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-0">
                <!-- Control Panel -->
                <div class="sticky-top bg-white p-3 border-bottom shadow-sm" style="z-index: 1050; top: 0;">
    <div class="row g-3 align-items-end">
        <!-- First row: Filters -->
        <div class="col-12 col-md-3">
            <label for="summaryYear" class="form-label small text-muted mb-1">Fiscal Year</label>
            <select class="form-select" id="summaryYear">
                <option value="">Select Year</option>
            </select>
        </div>
        <div class="col-12 col-md-3">
            <label for="summaryBranchFilter" class="form-label small text-muted mb-1">Branch Selection</label>
            <select class="form-select" id="summaryBranchFilter">
                <option value="all">All Locations</option>
                <!-- Branch options will be populated by JavaScript -->
            </select>
        </div>
        <div class="col-12 col-md-3">
            <label for="fromDate" class="form-label small text-muted mb-1">From Date</label>
            <input type="date" class="form-control" id="fromDate">
        </div>
        <div class="col-12 col-md-3">
            <label for="toDate" class="form-label small text-muted mb-1">To Date</label>
            <input type="date" class="form-control" id="toDate">
        </div>
        
        <!-- Second row: Action buttons -->
        <div class="col-12 col-md-4">
            <button id="generateSummaryBtn" class="btn btn-primary text-white w-100">
                <i class="fas fa-sync-alt me-2"></i>Generate Report
            </button>
        </div>
        <div class="col-12 col-md-8 d-flex justify-content-end">
            <div class="btn-group me-2">
                <button type="button" id="exportExcelBtn" class="btn btn-success">
                    <i class="fas fa-file-excel me-1"></i>Excel
                </button>
                <button type="button" id="exportPdfBtn" class="btn btn-danger">
                    <i class="fas fa-file-pdf me-1"></i>PDF
                </button>
            </div>
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                <i class="fas fa-times me-1"></i>Close
            </button>
        </div>
    </div>
</div>
<div class="tab-pane fade show active" id="performance-tab-pane" role="tabpanel">
    <div class="table-responsive">
        <table id="summaryReportTable" class="table table-striped table-hover">
            <thead class="table-light">
                <tr>
                    <th>Branch</th>
                    <th>Model</th>
                    <th>Quantity</th>
                    <th>Brand</th>
                </tr>
            </thead>
            <tbody id="summaryReportBody">
                <!-- Data will be loaded here -->
            </tbody>
        </table>
    </div>
</div>
            <!-- Modal Footer -->
            <div class="modal-footer bg-light">
                <div class="d-flex justify-content-between w-100 align-items-center">
                    <div class="text-muted small">
                        <i class="fas fa-database me-1"></i>
                        <span id="recordCount">0 records</span>
                    </div>
                   
                </div>
            </div>
        </div>
    </div>
</div>


<!-- File Upload Modal -->
<div class="modal fade" id="uploadSalesDataModal" tabindex="-1" aria-labelledby="uploadSalesDataModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadSalesDataModalLabel">Upload Sales Data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    CSV format: First column = Model, subsequent columns = Branch quantities
                </div>
                <form id="uploadSalesDataForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="salesDate" class="form-label">Sales Date</label>
                        <input type="date" class="form-control" id="salesDate" name="sales_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="file" class="form-label">Select CSV File</label>
                        <input type="file" class="form-control" id="file" name="file" accept=".csv" required>
                    </div>
                    <div class="d-flex justify-content-between">
                        <button type="submit" class="btn btn-primary text-white">
                            <i class="fas fa-upload me-2"></i>Upload
                        </button>
                        <a href="api/download_template.php" class="btn btn-primary text-white">
                            <i class="fas fa-download me-2"></i>Download Template
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
    <!-- Add Sale Modal -->
    <div class="modal fade" id="addSaleModal" tabindex="-1" aria-labelledby="addSaleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addSaleModalLabel">Add Sale</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="errorMessage" class="alert alert-danger" style="display: none;"></div>
                    <div id="successMessage" class="alert alert-success" style="display: none;"></div>
                    <form id="addSaleForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="saleDate" class="form-label">Date</label>
                                <input type="date" class="form-control" id="saleDate" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="branch" class="form-label">Branch</label>
                                <select class="form-select" id="branch" required>
                                    <option value="RXS-1">RXS-1</option>
                                    <option value="RXS-2">RXS-2</option>
                                    <option value="ANT-1">ANTIQUE-1</option>
                                    <option value="ANT-2">ANTIQUE-2</option>
                                    <option value="DEL-1">DELGADO-1</option>
                                    <option value="DEL-2">DELGADO-2</option>
                                    <option value="JAR-1">JARO-1</option>
                                    <option value="JAR-2">JARO-2</option>
                                    <option value="KAL-1">KALIBO-1</option>
                                    <option value="KAL-2">KALIBO-2</option>
                                    <option value="ALTA">ALTAVAS</option>
                                    <option value="EMAP">EMAP</option>
                                    <option value="CUL">CULASI</option>
                                    <option value="BAC">BACOLOD</option>
                                    <option value="PAS-1">PASSI-1</option>
                                    <option value="PAS-2">PASSI-2</option>
                                    <option value="BAL">BALASAN</option>
                                    <option value="GUA">GUIMARAS</option>
                                    <option value="PEM">PEMDI</option>
                                    <option value="EEM">EEMSI</option>
                                    <option value="AJU">AJUY</option>
                                    <option value="BAIL">BAILAN</option>
                                    <option value="MINDORO MB">MINDORO MB</option>
                                    <option value="MINDORO 3S">MINDORO 3S</option>
                                    <option value="MANSALAY">MANSALAY</option>
                                    <option value="K-RIDERS">K-RIDERS</option>
                                    <option value="IBAJAY">IBAJAY</option>
                                    <option value="NUMANCIA">NUMANCIA</option>
                                    <option value="HEADOFFICE">HEADOFFICE</option>
                                    <option value="CEBU">CEBU</option>
                                    <option value="GT">GT</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="brand" class="form-label">Brand</label>
                                <select class="form-select" id="brand" required>
                                    <option value="Suzuki">Suzuki</option>
                                    <option value="Honda">Honda</option>
                                    <option value="Kawasaki">Kawasaki</option>
                                    <option value="Yamaha">Yamaha</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="model" class="form-label">Model</label>
                                <select class="form-select" id="model" required>
                                    <!-- Options will be populated by JavaScript -->
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="quantity" class="form-label">Quantity</label>
                                <input type="number" class="form-control" id="quantity" min="1" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary text-white">Add Sale</button>
                    </form> 
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Sale Modal -->
    <div class="modal fade" id="editSaleModal" tabindex="-1" aria-labelledby="editSaleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editSaleModalLabel">Edit Sale</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editSaleForm">
                        <input type="hidden" id="editSaleId">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editSaleDate" class="form-label">Date</label>
                                <input type="date" class="form-control" id="editSaleDate" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editBranch" class="form-label">Branch</label>
                                <select class="form-select" id="editBranch" required>
                                    <option value="RXS-1">RXS-1</option>
                                    <option value="RXS-2">RXS-2</option>
                                    <option value="ANTIQUE-1">ANTIQUE-1</option>
                                    <option value="ANTIQUE-2">ANTIQUE-2</option>
                                    <option value="DELGADO-1">DELGADO-1</option>
                                    <option value="DELGADO-2">DELGADO-2</option>
                                    <option value="JARO-1">JARO-1</option>
                                    <option value="JARO-2">JARO-2</option>
                                    <option value="KALIBO-1">KALIBO-1</option>
                                    <option value="KALIBO-2">KALIBO-2</option>
                                    <option value="ALTAVAS">ALTAVAS</option>
                                    <option value="EMAP">EMAP</option>
                                    <option value="CULASI">CULASI</option>
                                    <option value="BACOLOD">BACOLOD</option>
                                    <option value="PASSI-1">PASSI-1</option>
                                    <option value="PASSI-2">PASSI-2</option>
                                    <option value="BALASAN">BALASAN</option>
                                    <option value="GUIMARAS">GUIMARAS</option>
                                    <option value="PEMDI">PEMDI</option>
                                    <option value="EEMSI">EEMSI</option>
                                    <option value="AJUY">AJUY</option>
                                    <option value="BAILAN">BAILAN</option>
                                    <option value="MINDORO MB">MINDORO MB</option>
                                    <option value="MINDORO 3S">MINDORO 3S</option>
                                    <option value="MANSALAY">MANSALAY</option>
                                    <option value="K-RIDERS">K-RIDERS</option>
                                    <option value="IBAJAY">IBAJAY</option>
                                    <option value="NUMANCIA">NUMANCIA</option>
                                    <option value="HEADOFFICE">HEADOFFICE</option>
                                    <option value="CEBU">CEBU</option>
                                    <option value="GT">GT</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editBrand" class="form-label">Brand</label>
                                <select class="form-select" id="editBrand" required>
                                    <option value="Suzuki">Suzuki</option>
                                    <option value="Honda">Honda</option>
                                    <option value="Kawasaki">Kawasaki</option>
                                    <option value="Yamaha">Yamaha</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editmodel" class="form-label">Model</label>
                                <select class="form-select" id="editmodel" required>
                                    <!-- Options will be populated by JavaScript -->
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editQuantity" class="form-label">Quantity</label>
                                <input type="number" class="form-control" id="editQuantity" min="1" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary text-white">Save Changes</button>
                    </form> 
                </div>
            </div>
        </div>
    </div>

<div class="modal fade" id="printOptionsModal" tabindex="-1" aria-labelledby="printOptionsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="printOptionsModalLabel">Print Sales Summary</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="printOptionsForm">
                    <div class="mb-3">
                        <label for="fromDate" class="form-label">From Date:</label>
                        <input type="date" class="form-control" id="fromDate" required>
                    </div>
                    <div class="mb-3">
                        <label for="toDate" class="form-label">To Date:</label>
                        <input type="date" class="form-control" id="toDate" required>
                    </div>
                    <div class="mb-3">
                        <label for="branchSelect" class="form-label">Branch:</label>
                        <select class="form-select" id="branchSelect">
                            <option value="all">All Branches</option>
                            <option value="RXS-1">RXS-1</option>
                            <option value="RXS-2">RXS-2</option>
                            <!-- Add all other branches here -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="outputFormat" class="form-label">Output Format:</label>
                        <select class="form-select" id="outputFormat">
                            <option value="pdf">PDF</option>
                            <option value="excel">Excel</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="confirmPrint">Generate Report</button>
            </div>
        </div>
    </div>
</div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="successMessage">Successful!</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmationModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this record?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary text-white" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmDeleteBtn" class="btn btn-primary text-white">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Duplicate Error Modal -->
    <div class="modal fade" id="duplicateErrorModal" tabindex="-1" aria-labelledby="duplicateErrorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="duplicateErrorModalLabel">Duplicate Record!</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="duplicateErrorMessage">A record with this name already exists.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary text-white" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Warning Modal -->
    <div class="modal fade" id="warningModal" tabindex="-1" role="dialog" aria-labelledby="warningModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="warningModalLabel">Warning</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="warningMessage"></p>
                </div>
            </div>
        </div>
    </div>

   <script src="js/sales_dashboard.js"></script>
</body>
</html>