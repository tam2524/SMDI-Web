<?php include '../api/auth.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>SMDI - LIAISON | Admin Dashboard</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <link rel="icon" href="../assets/img/smdi_logosmall.png" type="image/png">

    <!-- Icon Font Stylesheet -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="../lib/lightbox/css/lightbox.min.css" rel="stylesheet">
    <link href="../lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="../css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="../css/style.css" rel="stylesheet">

    <!-- PrintJS -->
    <link rel="stylesheet" href="https://printjs-4de6.kxcdn.com/print.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://printjs-4de6.kxcdn.com/print.min.js"></script>
    <script src="../js/script.js"></script>
    <script src="../js/user_dashboard.js"></script>
    <script src="../js/visitors.js"></script>
    <script src="../js/sales_dashboard.js"></script>




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
        /* Disable click */
        background-color: white;
        border-color: #ccc;
    }

    .pagination .page-item.disabled .page-link:hover {
        background-color: white;
        color: #ccc;
        /* No change on hover for disabled */
    }

    /* Tab styling */
    .nav-tabs .nav-link {
        color: #495057;
        border: 1px solid transparent;
        border-top-left-radius: 0.25rem;
        border-top-right-radius: 0.25rem;
    }

    .nav-tabs .nav-link.active {
        color: #000f71;
        background-color: #fff;
        border-color: #dee2e6 #dee2e6 #fff;
        font-weight: bold;
    }

    .nav-tabs .nav-link:hover {
        border-color: #e9ecef #e9ecef #dee2e6;
        color: #000f71;
    }

    .tab-content {
        padding: 0px 0;
    }

    /* Visitor Stats Cards */
    .visitor-card .card-title {
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
    }

    .visitor-card .card-text {
        font-size: 1.75rem;
        font-weight: bold;
    }

    /* Visitor Table */
    #visitorsTable {
        font-size: 0.9rem;
    }

    #visitorsTable th {
        white-space: nowrap;
    }
    </style>
</head>

<body>
    <!-- Navbar-->
    <div class="container-fluid fixed-top">
        <div class="container topbar bg-primary d-none d-lg-block">
            <div class="d-flex justify-content-between">
                <div class="top-info ps-2">
                    <small class="me-3"><i class="fas fa-map-marker-alt me-2 text-primary"></i> <a href="#"
                            class="text-white">1031, Victoria Building, Roxas Avenue, Roxas City, 5800</a></small>
                </div>
                <div class="top-link pe-2"></div>
            </div>
        </div>
        <div class="container px-0">
            <nav class="navbar navbar-light bg-white navbar-expand-lg">
                <a href="index.html" class="navbar-brand">
                    <img src="../assets/img/smdi_logo.jpg" alt="Company Logo" class="logo">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse"
                    aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarCollapse">
                    <div class="navbar-nav">
                       <a href='admin_dashboard.php' class='nav-item nav-link active'>Home</a>
                        <a href='admin_dashboard.php' class='nav-item nav-link active'>Dashboard</a>
                       <a href='admin_inventory.php' class='nav-item nav-link active'>Inventory</a>
                        <a href='../api/logout.php' class='nav-item nav-link active'>Logout</a> 
                    </div>
                </div>
            </nav>
        </div>
    </div>
    <!-- Navbar-->

    <!-- Main Container -->
    <div class="container-fluid py-5" style="margin-top: 120px;">
        <!-- Tab Navigation -->
        <ul class="nav nav-tabs" id="adminTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="records-tab" data-bs-toggle="tab" data-bs-target="#records"
                    type="button" role="tab" aria-controls="records" aria-selected="true">Records</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button"
                    role="tab" aria-controls="users" aria-selected="false">User Management</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="visitors-tab" data-bs-toggle="tab" data-bs-target="#visitors" type="button"
                    role="tab" aria-controls="visitors" aria-selected="false">Visitor Logs</button>
            </li>

            <li class="nav-item" role="presentation">
                <button class="nav-link" id="sales-tab" data-bs-toggle="tab" data-bs-target="#sales" type="button"
                    role="tab" aria-controls="sales" aria-selected="false">Sales</button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="adminTabsContent">
            <!-- Records Tab -->
            <div class="tab-pane fade show active" id="records" role="tabpanel" aria-labelledby="records-tab">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Records</h5>
                        <button class="btn btn-primary text-white mb-3" data-bs-toggle="modal"
                            data-bs-target="#addRecordModal">Add New Record</button>
                        <button class="btn btn-primary text-white mb-3" data-bs-toggle="modal"
                            data-bs-target="#printOptionsModal">Print Documents</button>
                        <button id="deleteSelectedButton" class="btn btn-primary text-white mb-3">Delete
                            Selected</button>

                        <!-- Search and Sort Options -->
                        <div class="mb-3 d-flex">
                            <input type="text" id="searchInput" class="form-control me-2" placeholder="Search...">
                        </div>

                        <!-- Table of Records -->
                        <table id="RecordTable" class="table table-striped">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAll"></th>
                                    <th>Family Name</th>
                                    <th>First Name</th>
                                    <th>Middle Name</th>
                                    <th>Plate Number</th>
                                    <th>MV File</th>
                                    <th>Branch</th>
                                    <th>Batch</th>
                                    <th>Remarks</th>
                                    <th class="no-print">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="RecordTableBody">
                                <!-- Records will be loaded here by AJAX -->
                            </tbody>
                        </table>

                        <!-- Pagination Controls -->
                        <nav aria-label="Page navigation">
                            <ul id="paginationControls" class="pagination">
                                <li id="prevPage" class="page-item">
                                    <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a>
                                </li>
                                <li id="nextPage" class="page-item">
                                    <a class="page-link" href="#">Next</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>

            <!-- User Management Tab -->
            <div class="tab-pane fade" id="users" role="tabpanel" aria-labelledby="users-tab">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">User Management</h5>
                        <button class="btn btn-primary text-white mb-3" data-bs-toggle="modal"
                            data-bs-target="#addUserModal">Add New User</button>

                        <!-- Search Users -->
                        <div class="mb-3 d-flex">
                            <input type="text" id="searchUserInput" class="form-control me-2"
                                placeholder="Search users...">
                        </div>

                        <!-- Users Table -->
                        <table id="usersTable" class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Full Name</th>
                                    <th>Position</th>
                                    <th>Branch</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="usersTableBody">
                                <!-- Users will be loaded here by AJAX -->
                            </tbody>
                        </table>

                        <!-- Users Pagination -->
                        <nav aria-label="Page navigation">
                            <ul id="usersPaginationControls" class="pagination">
                                <li id="prevUsersPage" class="page-item">
                                    <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a>
                                </li>
                                <li id="nextUsersPage" class="page-item">
                                    <a class="page-link" href="#">Next</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>


            <div class="tab-pane fade" id="sales" role="tabpanel" aria-labelledby="sales-tab">
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h1 class="h5 mb-0">Sales Records Management</h1>
                    </div>
                    <div class="card-body">
                        <!-- Action Buttons -->
                        <div class="action-buttons mb-4">
                            <button class="btn btn-primary text-white mb-2" data-bs-toggle="modal"
                                data-bs-target="#salesQuotaModal">
                                <i class="fas fa-chart-line me-2"></i>Set Sales Quotas
                            </button>
                            <button class="btn btn-primary text-white mb-2" data-bs-toggle="modal"
                                data-bs-target="#addSaleModal">
                                <i class="fas fa-plus-circle me-2"></i>Add New Sale
                            </button>
                            <button id="deleteSelectedButton" class="btn btn-primary text-white mb-2">
                                <i class="fas fa-trash-alt me-2"></i>Delete Selected
                            </button>
                            <button class="btn btn-primary text-white mb-2" data-bs-toggle="modal"
                                data-bs-target="#summaryReportModal">
                                <i class="fas fa-chart-pie me-2"></i>View Summary Report
                            </button>
                            <button class="btn btn-primary text-white mb-2" data-bs-toggle="modal"
                                data-bs-target="#uploadSalesDataModal">
                                <i class="fas fa-chart-pie me-2"></i>Import
                            </button>
                        </div>

                        <!-- Search and Sort -->
                        <div class="mb-3 search-sort-container d-flex">
                            <input type="text" id="searchInput" class="form-control me-2"
                                placeholder="Search by branch, brand or model..." aria-label="Search sales records">
                            <div class="dropdown">
                                <button class="btn btn-primary text-white dropdown-toggle" type="button"
                                    id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false"
                                    aria-haspopup="true">
                                    <i class="fas fa-sort me-1"></i> Sort by
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="sortDropdown">
                                    <li><a class="dropdown-item" href="#" data-sort="date_desc"><i
                                                class="fas fa-sort-numeric-down me-2"></i>Date (Newest First)</a></li>
                                    <li><a class="dropdown-item" href="#" data-sort="date_asc"><i
                                                class="fas fa-sort-numeric-up me-2"></i>Date (Oldest First)</a></li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li><a class="dropdown-item" href="#" data-sort="branch_asc"><i
                                                class="fas fa-sort-alpha-down me-2"></i>Branch (A-Z)</a></li>
                                    <li><a class="dropdown-item" href="#" data-sort="branch_desc"><i
                                                class="fas fa-sort-alpha-up me-2"></i>Branch (Z-A)</a></li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li><a class="dropdown-item" href="#" data-sort="brand_asc"><i
                                                class="fas fa-sort-alpha-down me-2"></i>Brand (A-Z)</a></li>
                                    <li><a class="dropdown-item" href="#" data-sort="brand_desc"><i
                                                class="fas fa-sort-alpha-up me-2"></i>Brand (Z-A)</a></li>
                                </ul>
                            </div>
                        </div>

                        <!-- Sales Table -->
                        <div class="table-responsive">
                            <table id="salesTable" class="table table-striped table-hover"
                                aria-describedby="salesTableDesc">
                                <caption id="salesTableDesc" class="visually-hidden">List of sales records with date,
                                    branch,
                                    brand, model and quantity information</caption>
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
                <div class="modal fade" id="salesQuotaModal" tabindex="-1" aria-labelledby="salesQuotaModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="salesQuotaModalLabel">Sales Quotas Management</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="d-flex justify-content-between mb-3">
                                    <button id="addQuotaBtn" class="btn btn-primary text-white">Add New Quota</button>
                                    <div class="input-group" style="width: 300px;">
                                        <input type="text" id="quotaSearchInput" class="form-control"
                                            placeholder="Search branches...">
                                        <button class="btn btn-outline-secondary" type="button" id="quotaSearchBtn">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Add/Edit Quota Form (initially hidden) -->
                                <div id="quotaFormContainer" style="display: none;">
                                    <div id="quotaErrorMessage" class="alert alert-danger" style="display: none;"></div>
                                    <div id="quotaSuccessMessage" class="alert alert-success" style="display: none;">
                                    </div>
                                    <form id="salesQuotaForm">
                                        <input type="hidden" id="quotaId">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="quotaYear" class="form-label">Year</label>
                                                <select class="form-select" id="quotaYear" required>
                                                    <option value="2025" selected>2025</option>
                                                    <option value="2026">2026</option>
                                                    <option value="2027">2027</option>
                                                    <option value="2028">2028</option>
                                                    <option value="2029">2029</option>
                                                    <option value="2030">2030</option>
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
                                                <input type="number" class="form-control" id="quotaAmount" min="1"
                                                    required>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-end">
                                            <button type="button" id="cancelQuotaBtn"
                                                class="btn btn-primary text-white me-2">Cancel</button>
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
                <!-- File Upload Modal -->
                <div class="modal fade" id="uploadSalesDataModal" tabindex="-1"
                    aria-labelledby="uploadSalesDataModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="uploadSalesDataModalLabel">Upload Sales Data</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    CSV format: First column = Model, subsequent columns = Branch quantities
                                </div>
                                <form id="uploadSalesDataForm" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label for="salesDate" class="form-label">Sales Date</label>
                                        <input type="date" class="form-control" id="salesDate" name="sales_date"
                                            required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="file" class="form-label">Select CSV File</label>
                                        <input type="file" class="form-control" id="file" name="file" accept=".csv"
                                            required>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <button type="submit" class="btn btn-primary text-white">
                                            <i class="fas fa-upload me-2"></i>Upload
                                        </button>
                                        <a href="../api/download_template.php" class="btn btn-primary text-white">
                                            <i class="fas fa-download me-2"></i>Download Template
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Add Sale Modal -->
                <div class="modal fade" id="addSaleModal" tabindex="-1" aria-labelledby="addSaleModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addSaleModalLabel">Add Sale</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
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
                                                <option value="RXS-S">RXS-S</option>
                                                <option value="RXS-H">RXS-H</option>
                                                <option value="ANT-1">ANT-1</option>
                                                <option value="ANT-2">ANT-2</option>
                                                <option value="SDH">SDH</option>
                                                <option value="SDS">SDS</option>
                                                <option value="JAR-1">JAR-1</option>
                                                <option value="JAR-2">JAR-2</option>
                                                <option value="SKM">SKM</option>
                                                <option value="SKS">SKS</option>
                                                <option value="ALTA">ALTA</option>
                                                <option value="EMAP">EMAP</option>
                                                <option value="CUL">CUL</option>
                                                <option value="BAC">BAC</option>
                                                <option value="PAS-1">PAS-1</option>
                                                <option value="PAS-2">PAS-2</option>
                                                <option value="BAL">BAL</option>
                                                <option value="GUIM">GUIM</option>
                                                <option value="PEMDI">PEMDI</option>
                                                <option value="EEM">EEM</option>
                                                <option value="AJU">AJU</option>
                                                <option value="BAIL">BAIL</option>
                                                <option value="3SMB">MINDORO MB</option>
                                                <option value="3SMIN">MINDORO 3S</option>
                                                <option value="MAN">MANSALAY</option>
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
                <div class="modal fade" id="editSaleModal" tabindex="-1" aria-labelledby="editSaleModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editSaleModalLabel">Edit Sale</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
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
                                                <option value="RXS-S">RXS-S</option>
                                                <option value="RXS-H">RXS-H</option>
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
                                                <option value="MINO">MINDORO MB</option>
                                                <option value="MIN">MINDORO 3S</option>
                                                <option value="SALAY">MANSALAY</option>
                                                <option value="K-RID">K-RIDERS</option>
                                                <option value="IBAJAY">IBAJAY</option>
                                                <option value="NUMANCIA">NUMANCIA</option>
                                                <option value="HEADOFFICE">HEADOFFICE</option>
                                                <option value="CEBU">CEBU</option>
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
                                            <input type="number" class="form-control" id="editQuantity" min="1"
                                                required>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary text-white">Save Changes</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="printOptionsModal" tabindex="-1" aria-labelledby="printOptionsModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="printOptionsModalLabel">Print Sales Summary</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
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
                                            <option value="RXS-S">RXS-S</option>
                                            <option value="RXS-H">RXS-H</option>
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


                <!-- Summary Report Modal -->
                <div class="modal fade" id="summaryReportModal" tabindex="-1" aria-labelledby="summaryReportModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-fullscreen-lg-down">
                        <div class="modal-content border-0">
                            <!-- Modal Header -->
                            <div class="modal-header bg-primary text-white">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-chart-bar fs-4 me-3"></i>
                                    <div>
                                        <h5 class="modal-title mb-0 text-white" id="summaryReportModalLabel">Sales
                                            Summary Report
                                        </h5>
                                        <small class="opacity-75">Generate sales reports by branch, brand, and date
                                            range</small>
                                    </div>
                                </div>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>

                            <!-- Modal Body -->
                            <div class="modal-body px-4 pt-4 pb-2">

                                <!-- Filter Panel -->
                                <div class="bg-light border rounded shadow-sm p-4 mb-3">
                                    <form class="row g-4 align-items-end">

                                        <!-- Month Filter -->
                                        <div class="col-12 col-md-3">
                                            <label for="summaryMonthFilter"
                                                class="form-label fw-semibold text-muted">Month</label>
                                            <select class="form-select" id="summaryMonthFilter">
                                                <option value="all">All Months</option>
                                                <option value="01">January</option>
                                                <option value="02">February</option>
                                                <option value="03">March</option>
                                                <option value="04">April</option>
                                                <option value="05">May</option>
                                                <option value="06">June</option>
                                                <option value="07">July</option>
                                                <option value="08">August</option>
                                                <option value="09">September</option>
                                                <option value="10">October</option>
                                                <option value="11">November</option>
                                                <option value="12">December</option>
                                            </select>
                                        </div>

                                        <!-- Year Filter -->
                                        <div class="col-12 col-md-3">
                                            <label for="summaryYearFilter"
                                                class="form-label fw-semibold text-muted">Year</label>
                                            <input type="number" class="form-control" id="summaryYearFilter" min="2000"
                                                max="2100" value="2025">
                                        </div>

                                        <!-- Branch Filter -->
                                        <div class="col-12 col-md-3">
                                            <label for="summaryBranchFilter"
                                                class="form-label fw-semibold text-muted">Branch</label>
                                            <select class="form-select" id="summaryBranchFilter">
                                                <option value="all">All Locations</option>
                                                <option value="RXS-S">RXS-S</option>
                                                <option value="RXS-H">RXS-H</option>
                                                <option value="ANT-1">ANT-1</option>
                                                <option value="ANT-2">ANT-2</option>
                                                <option value="SDH">SDH</option>
                                                <option value="SDS">SDS</option>
                                                <option value="JAR-1">JAR-1</option>
                                                <option value="JAR-2">JAR-2</option>
                                                <option value="SKM">SKM</option>
                                                <option value="SKS">SKS</option>
                                                <option value="ALTA">ALTA</option>
                                                <option value="EMAP">EMAP</option>
                                                <option value="CUL">CUL</option>
                                                <option value="BAC">BAC</option>
                                                <option value="PAS-1">PAS-1</option>
                                                <option value="PAS-2">PAS-2</option>
                                                <option value="BAL">BAL</option>
                                                <option value="GUIM">GUIM</option>
                                                <option value="PEMDI">PEMDI</option>
                                                <option value="EEM">EEM</option>
                                                <option value="AJU">AJU</option>
                                                <option value="BAIL">BAIL</option>
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

                                        <!-- Brand Filter -->
                                        <div class="col-12 col-md-3">
                                            <label for="summaryBrandFilter"
                                                class="form-label fw-semibold text-muted">Brand</label>
                                            <select class="form-select" id="summaryBrandFilter">
                                                <option value="all">All Brands</option>
                                                <option value="Suzuki">Suzuki</option>
                                                <option value="Honda">Honda</option>
                                                <option value="Kawasaki">Kawasaki</option>
                                                <option value="Yamaha">Yamaha</option>
                                            </select>
                                        </div>

                                        <!-- Action Button -->
                                        <div class="col-12 text-end">
                                            <button type="button" id="exportExcelBtn" class="btn btn-success px-4">
                                                <i class="fas fa-file-excel me-2"></i>Export to Excel
                                            </button>
                                        </div>

                                    </form>
                                </div>

                            </div>

                        </div>
                    </div>
                </div>
            </div>


            <div class="tab-pane fade" id="visitors" role="tabpanel" aria-labelledby="visitors-tab">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Website Visitor Statistics</h5>

                        <!-- Summary Cards -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card text-white bg-info">
                                    <div class="card-body">
                                        <h6 class="card-title">Total Visits</h6>
                                        <h3 id="totalVisits" class="card-text">0</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-white bg-info">
                                    <div class="card-body">
                                        <h6 class="card-title">Unique Visitors</h6>
                                        <h3 id="uniqueVisitors" class="card-text">0</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-white bg-info">
                                    <div class="card-body">
                                        <h6 class="card-title">Today's Visits</h6>
                                        <h3 id="todayVisits" class="card-text">0</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-white bg-info">
                                    <div class="card-body">
                                        <h6 class="card-title">This Month</h6>
                                        <h3 id="monthVisits" class="card-text">0</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Date Range Filter -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="visitorStartDate" class="form-label">From Date</label>
                                <input type="date" class="form-control" id="visitorStartDate">
                            </div>
                            <div class="col-md-4">
                                <label for="visitorEndDate" class="form-label">To Date</label>
                                <input type="date" class="form-control" id="visitorEndDate">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button id="filterVisitorsBtn" class="btn btn-primary text-white">Filter</button>
                                <button id="resetVisitorFilterBtn" class="btn btn-secondary ms-2">Reset</button>
                            </div>
                        </div>

                        <!-- Visitor Log Table -->
                        <div class="table-responsive">
                            <table id="visitorsTable" class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>IP Address</th>
                                        <th>Page Visited</th>
                                        <th>Device</th>
                                        <th>Visit Time</th>
                                    </tr>
                                </thead>
                                <tbody id="visitorsTableBody">
                                    <!-- Visitor logs will be loaded here by AJAX -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <nav aria-label="Page navigation">
                            <ul id="visitorsPaginationControls" class="pagination">
                                <li id="prevVisitorsPage" class="page-item">
                                    <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a>
                                </li>
                                <li id="nextVisitorsPage" class="page-item">
                                    <a class="page-link" href="#">Next</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>





    </div>

    </div>

    <!-- Add Record Modal -->
    <div class="modal fade" id="addRecordModal" tabindex="-1" aria-labelledby="addRecordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addRecordModalLabel">Add Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="errorMessage" class="alert alert-danger" style="display: none;"></div>
                    <div id="successMessage" class="alert alert-success" style="display: none;"></div>
                    <form id="addRecordForm" action="../api/add_Record.php" method="post">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="familyName" class="form-label">Family Name</label>
                                <input type="text" class="form-control" id="familyName" name="familyName" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="firstName" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="firstName" name="firstName" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="middleName" class="form-label">Middle Name</label>
                                <input type="text" class="form-control" id="middleName" name="middleName" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="plateNumber" class="form-label">Plate Number</label>
                                <input type="text" class="form-control" id="plateNumber" name="plateNumber" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="mvFile" class="form-label">MV File</label>
                                <input type="text" class="form-control" id="mvFile" name="mvFile" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="branch" class="form-label">Branch</label>
                                <input type="text" class="form-control" id="branch" name="branch" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="batch" class="form-label">Batch</label>
                                <input type="text" class="form-control" id="batch" name="batch" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="remarks" class="form-label">Remarks</label>
                                <input type="text" class="form-control" id="remarks" name="remarks" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="date_reg" class="form-label">Date Reg</label>
                                <input type="text" class="form-control" id="date_reg" name="date_reg" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary text-white">Add Record</button>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <!-- Edit Modal -->
    <div class="modal fade" id="editRecordModal" tabindex="-1" aria-labelledby="editRecordModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editRecordModalLabel">Edit Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editRecordForm" action="edit_Record.php" method="post">
                        <input type="hidden" id="editRecordId" name="Record_id">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editFamilyName" class="form-label">Family Name</label>
                                <input type="text" class="form-control" id="editFamilyName" name="familyName" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editFirstName" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="editFirstName" name="firstName" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editMiddleName" class="form-label">Middle Name</label>
                                <input type="text" class="form-control" id="editMiddleName" name="middleName" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editPlateNumber" class="form-label">Plate Number</label>
                                <input type="text" class="form-control" id="editPlateNumber" name="plateNumber"
                                    required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editMvFile" class="form-label">MV File</label>
                                <input type="text" class="form-control" id="editMvFile" name="mvFile" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="editBranch" class="form-label">Branch</label>
                                <input type="text" class="form-control" id="editBranch" name="branch" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="editBatch" class="form-label">Batch</label>
                                <input type="text" class="form-control" id="editBatch" name="batch" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editRemarks" class="form-label">Remarks</label>
                                <input type="text" class="form-control" id="editRemarks" name="remarks" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editDatereg" class="form-label">DateReg</label>
                                <input type="text" class="form-control" id="editDateReg" name="date_reg" required>
                            </div>
                        </div>
                        <button type="submit" class="btn text-white btn-primary ">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Print Options Modal -->
    <div class="modal fade" id="printOptionsModal" tabindex="-1" aria-labelledby="printOptionsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="printOptionsModalLabel">Print Options</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="printOptionsForm">
                        <div class="mb-3">
                            <label for="documentType" class="form-label">Document Type</label>
                            <select class="form-select" id="documentType">
                                <option value="masterlists">Masterlists</option>
                                <option value="labels">Labels</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="sortBy" class="form-label">Sort Document By</label>
                            <select class="form-select" id="sortBy">
                                <option value="all">All</option>
                                <option value="customerBatchRange">Customer Batch Range</option>
                                <option value="familyName">Family Name</option> <!-- Added Family Name option -->
                            </select>
                        </div>
                        <div class="mb-3" id="batchRange" style="display: none;">
                            <label for="fromBatch" class="form-label">From:</label>
                            <input type="text" class="form-control" id="fromBatch">
                            <label for="toBatch" class="form-label">To:</label>
                            <input type="text" class="form-control" id="toBatch">
                        </div>
                        <div class="mb-3" id="familyNameRange" style="display: none;">
                            <label for="nameRange" class="form-label">Ex: A-B = Show All Records with Family Name
                                starting with A only</label>
                            <label for="fromLetter" class="form-label">From Letter:</label>
                            <input type="text" class="form-control" id="fromLetter" placeholder="Enter starting letter">
                            <label for="toLetter" class="form-label">To Letter:</label>
                            <input type="text" class="form-control" id="toLetter" placeholder="Enter ending letter">
                        </div>
                        <div class="mb-3">
                            <label for="outputFormat" class="form-label">Output Format</label>
                            <select class="form-select" id="outputFormat">
                                <option value="pdf">PDF</option>
                                <option value="excel">Excel</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary text-white" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary text-white" id="confirmPrint">Print</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="userErrorMessage" class="alert alert-danger" style="display: none;"></div>
                    <div id="userSuccessMessage" class="alert alert-success" style="display: none;"></div>
                    <form id="addUserForm">
                        <div class="mb-3">
                            <label for="newUsername" class="form-label">Username*</label>
                            <input type="text" class="form-control" id="newUsername" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="newFullName" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="newFullName" name="fullName">
                        </div>
                        <div class="mb-3">
                            <label for="newPosition" class="form-label">Position</label>
                            <input type="text" class="form-control" id="newPosition" name="position">
                        </div>
                        <div class="mb-3">
                            <label for="newBranch" class="form-label">Branch</label>
                            <input type="text" class="form-control" id="newBranch" name="branch">
                        </div>
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">Password*</label>
                            <input type="password" class="form-control" id="newPassword" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirm Password*</label>
                            <input type="password" class="form-control" id="confirmPassword" name="confirmPassword"
                                required>
                        </div>

                        <button type="submit" class="btn btn-primary text-white">Add User</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editUserForm">
                        <input type="hidden" id="editUserId" name="id">
                        <div class="mb-3">
                            <label for="editUsername" class="form-label">Username*</label>
                            <input type="text" class="form-control" id="editUsername" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="editFullName" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="editFullName" name="fullName">
                        </div>
                        <div class="mb-3">
                            <label for="editPosition" class="form-label">Position</label>
                            <input type="text" class="form-control" id="editPosition" name="position">
                        </div>
                        <div class="mb-3">
                            <label for="editUserBranch" class="form-label">Branch</label>
                            <input type="text" class="form-control" id="editUserBranch" name="branch">
                        </div>

                        <div class="mb-3">
                            <label for="editPassword" class="form-label">New Password (leave blank to keep
                                current)</label>
                            <input type="password" class="form-control" id="editPassword" name="password">
                        </div>
                        <button type="submit" class="btn btn-primary text-white">Save Changes</button>
                    </form>
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
    <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel"
        aria-hidden="true">
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
    <div class="modal fade" id="duplicateErrorModal" tabindex="-1" aria-labelledby="duplicateErrorModalLabel"
        aria-hidden="true">
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
    <div class="modal fade" id="warningModal" tabindex="-1" role="dialog" aria-labelledby="warningModalLabel"
        aria-hidden="true">
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


</body>

</html>