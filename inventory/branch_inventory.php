<?php include '../api/auth.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>SMDI - INVENTORY | The Highest Levels of Service</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <link rel="icon" href="../assets/img/smdi_logosmall.png" type="image/png">

    <!-- Icon Font Stylesheet -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Libraries Stylesheet -->
    <link href="../lib/lightbox/css/lightbox.min.css" rel="stylesheet">
    <link href="../lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />


    <!-- Bootstrap CSS -->
    <link href="../css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="../css/style.css" rel="stylesheet">

    <!-- PrintJS -->
    <link rel="stylesheet" href="https://printjs-4de6.kxcdn.com/print.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://printjs-4de6.kxcdn.com/print.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <style>
    #branchMap {
        height: 600px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .branch-marker {
        background-color: #000f71;
        color: white;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        cursor: pointer;
    }

    .current-branch {
        background-color: #28a745;
        box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.3);
    }

    .search-container {
        margin-bottom: 20px;
    }

    .model-list {
        max-height: 500px;
        overflow-y: auto;
    }

    .model-item {
        transition: all 0.2s;
        cursor: pointer;
    }

    .model-item:hover {
        background-color: #f8f9fa;
    }

    .nav-tabs .nav-link.active {
        font-weight: 600;
        border-bottom: 3px solid #000f71;
    }

    .table-responsive {
        margin-bottom: 20px;
    }

    .model-card {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 10px 12px;
        background: white;
        transition: all 0.3s ease;
        height: 70px;
        margin-bottom: 10px;
    }

    .model-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        border-color: #000f71;
    }

    .model-name {
        font-size: 0.9rem;
        font-weight: 600;
        color: #333;
        flex: 1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .quantity-badge {
        background-color: #000f71;
        color: white;
        border-radius: 20px;
        padding: 3px 10px;
        font-size: 0.9rem;
        font-weight: bold;
        min-width: 40px;
        text-align: center;
    }

    .nav-tabs .nav-link.active {
        font-weight: 600;
        border-bottom: 3px solid #000f71;
    }

    .branch-marker {
        background-color: #000f71;
        color: white;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }

    .modal.fade.show {
        display: block !important;
        background-color: rgba(0, 0, 0, 0.5);
    }

    .modal-backdrop.fade.show {
        opacity: 0.5 !important;
    }

    @media (min-width: 1200px) {
        .model-card-container {
            flex: 0 0 10%;
            max-width: 10%;
        }
    }

    /* Pagination Styles */
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

    .sortable-header {
        cursor: pointer;
    }

    .sortable-header:hover {
        background-color: #f8f9fa;
    }

    /* Brand-specific colors */
    .border-primary {
        border-color: #000f71 !important;
    }

    .bg-primary-light {
        background-color: rgba(0, 15, 113, 0.1) !important;
    }

    .border-danger {
        border-color: #dc3545 !important;
    }

    .bg-danger-light {
        background-color: rgba(220, 53, 69, 0.1) !important;
    }

    .border-black {
        border-color: #000000ff !important;
    }

    .bg-black-light {
        background-color: rgba(0, 0, 0, 0.1) !important;
    }

    .border-success {
        border-color: #28a745 !important;
    }

    .bg-success-light {
        background-color: rgba(40, 167, 69, 0.1) !important;
    }

    /* Update model card hover effect */
    .model-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        opacity: 0.9;
    }

    /* Update quantity badge to match brand */
    .model-card.border-primary .quantity-badge {
        background-color: #000f71;
    }

    .model-card.border-danger .quantity-badge {
        background-color: #dc3545;
    }

    .model-card.border-black .quantity-badge {
        background-color: #000000;
    }

    .model-card.border-success .quantity-badge {
        background-color: #28a745;
    }

    .btn-group {
        margin-right: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .btn-group .btn {
        border-radius: 0;
        border-left: 1px solid rgba(255, 255, 255, 0.2);
    }

    .btn-group .btn:first-child {
        border-top-left-radius: 4px;
        border-bottom-left-radius: 4px;
        border-left: none;
    }

    .btn-group .btn:last-child {
        border-top-right-radius: 4px;
        border-bottom-right-radius: 4px;
    }

    .btn-group .btn:hover {
        background-color: #0069d9;
        color: white;
    }

    .btn-group .btn i {
        margin-right: 5px;
    }

    .card-header .btn-link {
        text-decoration: none;
        color: #000f71;
        font-weight: 600;
        width: 100%;
        text-align: left;
        padding: 0.75rem 1.25rem;
    }

    .card-header .btn-link:hover {
        color: #000f71;
        text-decoration: underline;
    }

    .card-header .btn-link:after {
        content: '\f078';
        font-family: 'Font Awesome 5 Free';
        font-weight: 900;
        float: right;
        transition: transform 0.3s;
    }

    .card-header .btn-link[ aria-expanded='true']:after {
        transform: rotate(180deg);
    }

    .card-body {
        padding: 1rem 1.25rem;
    }

    .table-sm th,
    .table-sm td {
        padding: 0.5rem;
    }

    /* Add these styles to your existing CSS */
    .transfer-search-result {
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 8px;
        background: white;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .transfer-search-result:hover {
        border-color: #0d6efd;
        background-color: #f8f9fa;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .transfer-search-result.selected {
        border-color: #198754;
        background-color: #d1e7dd;
    }

    .transfer-search-result .engine-number {
        font-weight: 600;
        color: #0d6efd;
        font-size: 0.95rem;
    }

    .transfer-search-result .model-info {
        font-size: 0.85rem;
        color: #6c757d;
        margin-bottom: 4px;
    }

    .transfer-search-result .branch-info {
        font-size: 0.8rem;
        color: #868e96;
    }

    .selected-motorcycle-item {
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 10px;
        margin-bottom: 8px;
        background: white;
        animation: fadeIn 0.3s ease;
    }

    .selected-motorcycle-item:hover {
        background-color: #f8f9fa;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        border: 1px solid rgba(0, 0, 0, 0.125);
    }

    .card-header {
        border-bottom: 1px solid rgba(0, 0, 0, 0.125);
    }

    .form-label {
        font-weight: 500;
    }

    /* Scrollbar styling */
    #searchResults::-webkit-scrollbar,
    #selectedMotorcyclesList::-webkit-scrollbar {
        width: 6px;
    }

    #searchResults::-webkit-scrollbar-track,
    #selectedMotorcyclesList::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }

    #searchResults::-webkit-scrollbar-thumb,
    #selectedMotorcyclesList::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 3px;
    }

    #searchResults::-webkit-scrollbar-thumb:hover,
    #selectedMotorcyclesList::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }

    .specific-details-container {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        border: 1px solid #e9ecef;
    }

    .specific-details-row {
        background: white;
        border-radius: 6px;
        padding: 10px;
        margin-bottom: 10px;
    }

    .specific-details-row:last-child {
        margin-bottom: 0;
        border-bottom: none;
    }

/* Sell button styling */
.sell-btn {
    margin-left: 5px;
}

/* Modal field styling */
#codFields, #installmentFields {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    margin-top: 10px;
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
                <a class="navbar-brand">
                    <img src="../assets/img/smdi_logo.jpg" alt="SMDI Logo" class="logo">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse"
                    aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarCollapse">
                    <div class="navbar-nav">
                        <a href="../inventory/branch_inventory.php" class="nav-item nav-link active">Home</a>

                        <a href="../api/logout.php" class="nav-item nav-link active">Logout</a>
                    </div>
                </div>
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <main class="container-fluid py-5" style="margin-top: 20px;">
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h1 class="h5 mb-0">Motorcycle Inventory Management</h1>
            </div>
            <div class="card-body">
                <ul class="nav nav-tabs mb-4" id="inventoryTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="find-tab" data-bs-toggle="tab"
                            data-bs-target="#find" type="button" role="tab">Find</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="inventory-tab" data-bs-toggle="tab"
                            data-bs-target="#inventory" type="button" role="tab">Overview</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="management-tab" data-bs-toggle="tab" data-bs-target="#management"
                            type="button" role="tab">Inventory Management</button>
                    </li>
                </ul>

                <div class="tab-content" id="inventoryTabContent">
                     

                    <div class="tab-pane fade show active" id="find" role="tabpanel">
                        <div class="container-fluid py-5">
                            <div class="card mb-4">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="search-container">
                                                <div class="input-group">
                                                    <input type="text" id="searchModel" class="form-control"
                                                        placeholder="Search motorcycle model...">
                                                    <button class="btn btn-primary text-white" id="searchModelBtn">
                                                        <i class="bi bi-search"></i> Search
                                                    </button>
                                                </div>
                                            </div>
                                            <div id="branchMap"></div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card h-100">
                                                <div class="card-header bg-primary text-white">
                                                    <h5 class="mb-0 text-white">Available Inventory</h5>
                                                </div>
                                                <div class="card-body">
                                                    <div id="branchInfo" class="mb-3">
                                                        <p class="text-muted">Click on a branch to view inventory</p>
                                                    </div>
                                                    <div id="modelList" class="model-list"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                

                    <!-- Management Tab -->
                    <div class="tab-pane fade" id="management" role="tabpanel">
                        <div class="d-flex justify-content-between mb-4">
                            <div>
                                <button class="btn btn-primary text-white me-2" data-bs-toggle="modal"
                                    data-bs-target="#addMotorcycleModal">
                                    <i class="bi bi-plus-circle"></i> Add Motorcycle
                                </button>
                                <button id="transferSelectedBtn" class="btn btn-primary text-white" disabled>
                                    <i class="bi bi-truck"></i> Transfer
                                </button>
                            </div>
                            <div class="input-group" style="max-width: 300px;">
                                <input type="text" id="searchInventory" class="form-control"
                                    placeholder="Search inventory...">
                                <button class="btn btn-primary text-white" type="button" id="searchInventoryBtn">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped" id="inventoryTable">
                                <thead>
                                   <tr>
                                        <th>Invoice No./MT</th>
                                        <th class='sortable-header' data-sort='date_delivered'>Date Delivered</th>
                                        <th class='sortable-header' data-sort='brand'>Brand</th>
                                        <th class='sortable-header' data-sort='model'>Model</th>
                                        <th>Engine No.</th>
                                        <th>Frame No.</th>
                                        <th>Color</th>
                                        <th>LCP</th>
                                        <th class='sortable-header' data-sort='current_branch'>Current Branch</th>
                                        <!-- <th class = 'sortable-header' data-sort = 'status'>Status</th> -->
                                        <th class='no-print'>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="inventoryTableBody">
                                    <tr>
                                        <td colspan="11" class="text-center py-5">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <nav aria-label="Inventory pagination">
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

                    <div class='tab-pane fade show active' id='inventory' role='tabpanel'>
                        <div class='row mb-4'>
                            <div class='col-md-6'>
                                <h4>Inventory Overview</h4>
                            </div>
                            <div class='col-md-6 text-end'>
                                <div class='input-group' style='max-width: 300px; margin-left: auto;'>
                                    <input type='text' id='searchDashboard' class='form-control'
                                        placeholder='Search models...'>
                                    <button class='btn btn-primary text-white' type='button' id='searchDashboardBtn'>
                                        <i class='bi bi-search'></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class='row' id='inventoryCards'>
                            <!-- Inventory cards will be loaded here -->
                            <div class='col-12 text-center py-5'>
                                <div class='spinner-border text-primary' role='status'>
                                    <span class='visually-hidden'>Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailsModalLabel">Motorcycle Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="motorcycleDetails">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>


    <!-- Add Motorcycle Modal -->
    <div class='modal fade' id='addMotorcycleModal' tabindex='-1' aria-labelledby='addMotorcycleModalLabel'
        aria-hidden='true'>
        <div class='modal-dialog modal-lg'>
            <div class='modal-content'>
                <div class='modal-header'>
                    <h5 class='modal-title' id='addMotorcycleModalLabel'>Add Motorcycle to Inventory</h5>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body'>
                    <form id='addMotorcycleForm'>
                        <!-- Invoice Information -->
                        <div class='row mb-4'>
                            <div class='col-md-6 mb-3'>
                                <label for='invoiceNumber' class='form-label'>Invoice Number/MT</label>
                                <input type='text' class='form-control' id='invoiceNumber' required>
                            </div>
                            <div class='col-md-6 mb-3'>
                                <label for='dateDelivered' class='form-label'>Date Delivered</label>
                                <input type='date' class='form-control' id='dateDelivered' required>
                            </div>
                            <div class='col-md-6 mb-3'>
                            <label for='branch' class='form-label'>Branch</label>
                            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') { ?>
                                <!-- Admin can select any branch -->
                                <select class='form-select' id='branch' required>
                                    <option value='HEADOFFICE'>Head Office</option>
                                    <option value='RXS-S'>RXS-S</option>
                                    <option value='RXS-H'>RXS-H</option>
                                    <option value='ANT-1'>ANT-1</option>
                                    <option value='ANT-2'>ANT-2</option>
                                    <option value='SDH'>SDH</option>
                                    <option value='SDS'>SDS</option>
                                    <option value='JAR-1'>JAR-1</option>
                                    <option value='JAR-2'>JAR-2</option>
                                    <option value='SKM'>SKM</option>
                                    <option value='SKS'>SKS</option>
                                    <option value='ALTA'>ALTA</option>
                                    <option value='EMAP'>EMAP</option>
                                    <option value='CUL'>CUL</option>
                                    <option value='BAC'>BAC</option>
                                    <option value='PAS-1'>PAS-1</option>
                                    <option value='PAS-2'>PAS-2</option>
                                    <option value='BAL'>BAL</option>
                                    <option value='GUIM'>GUIM</option>
                                    <option value='PEMDI'>PEMDI</option>
                                    <option value='EEM'>EEM</option>
                                    <option value='AJU'>AJU</option>
                                    <option value='BAIL'>BAIL</option>
                                    <option value='3SMB'>MINDORO MB</option>
                                    <option value='3SMIN'>MINDORO 3S</option>
                                    <option value='MAN'>MANSALAY</option>
                                    <option value='K-RIDERS'>K-RIDERS</option>
                                    <option value='IBAJAY'>IBAJAY</option>
                                    <option value='NUMANCIA'>NUMANCIA</option>
                                    <option value='HEADOFFICE'>HEADOFFICE</option>
                                    <option value='CEBU'>CEBU</option>
                                </select>
                            <?php } else { ?>
                                <!-- Regular users can only add to their own branch -->
                                <input type='text' class='form-control' id='branch' value="<?php echo $_SESSION['user_branch']; ?>" readonly>
                                <input type='hidden' id='branch' value="<?php echo $_SESSION['user_branch']; ?>">
                            <?php } ?>
                        </div>
                        </div>

                        <hr>

                        <!-- Motorcycle Models -->
                        <h5 class='mb-3'>Motorcycle Models</h5>
                        <div id='modelFormsContainer'>
                            <!-- Model forms will be added here dynamically -->
                        </div>

                        <button type='button' id='addModelBtn' class='btn btn-secondary mt-3'>
                            <i class='bi bi-plus-circle'></i> Add Another Model
                        </button>

                        <div class='d-grid mt-4'>
                            <button type='submit' class='btn btn-primary text-white'>Add Motorcycles</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

  <!-- Update the model form template in your HTML -->
<template id="modelFormTemplate">
    <div class="model-form card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="model-number">Model #1</span>
            <button type="button" class="btn btn-sm btn-danger remove-model-btn">
                <i class="bi bi-trash"></i> Remove
            </button>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Brand</label>
                    <select class="form-select model-brand" required>
                        <option value="">Select Brand</option>
                        <option value="Suzuki">Suzuki</option>
                        <option value="Honda">Honda</option>
                        <option value="Kawasaki">Kawasaki</option>
                        <option value="Yamaha">Yamaha</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Model Name</label>
                    <input type="text" class="form-control model-name" required>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Quantity</label>
                    <input type="number" class="form-control model-quantity" min="1" value="1" required>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Color</label>
                    <input type="text" class="form-control model-color" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">LCP</label>
                    <input type="number" step="0.01" class="form-control model-lcp">
                </div>
            </div>

            <!-- Specific Model Details Container -->
            <div class="specific-details-container mt-3" style="display: none;">
                <h6 class="fw-semibold mb-3">Specific Model Details</h6>
                <div class="specific-details-rows">
                    <!-- Individual rows will be added here dynamically -->
                </div>
            </div>
        </div>
    </div>
</template>

    <!-- Edit Motorcycle Modal -->
    <div class='modal fade' id='editMotorcycleModal' tabindex='-1' aria-labelledby='editMotorcycleModalLabel'
        aria-hidden='true'>
        <div class='modal-dialog modal-lg'>
            <div class='modal-content'>
                <div class='modal-header'>
                    <h5 class='modal-title' id='editMotorcycleModalLabel'>Edit Motorcycle Details</h5>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body'>
                    <form id='editMotorcycleForm'>
                        <input type='hidden' id='editId'>
                        <div class='row'>
                            <div class='col-md-6 mb-3'>
                                <label for='editDateDelivered' class='form-label'>Date Delivered</label>
                                <input type='date' class='form-control' id='editDateDelivered' required>
                            </div>
                            <div class='col-md-6 mb-3'>
                                <label for='editInvoiceNumber' class='form-label'>Invoice Number/MT</label>
                                <input type='text' class='form-control' id='editInvoiceNumber' readonly>
                            </div>
                        </div>
                        <div class='row'>
                            <div class='col-md-6 mb-3'>
                                <label for='editBrand' class='form-label'>Brand</label>
                                <select class='form-select' id='editBrand' required>
                                    <option value='Suzuki'>Suzuki</option>
                                    <option value='Honda'>Honda</option>
                                    <option value='Kawasaki'>Kawasaki</option>
                                    <option value='Yamaha'>Yamaha</option>
                                </select>
                            </div>
                            <div class='col-md-6 mb-3'>
                                <label for='editModel' class='form-label'>Model</label>
                                <input type='text' class='form-control' id='editModel' required>
                            </div>
                        </div>
                        <div class='row'>
                            <div class='col-md-6 mb-3'>
                                <label for='editEngineNumber' class='form-label'>Engine Number</label>
                                <input type='text' class='form-control' id='editEngineNumber' required>
                            </div>
                            <div class='col-md-6 mb-3'>
                                <label for='editFrameNumber' class='form-label'>Frame Number</label>
                                <input type='text' class='form-control' id='editFrameNumber' required>
                            </div>
                        </div>
                        <div class='row'>
                            <div class='col-md-6 mb-3'>
                                <label for='editColor' class='form-label'>Color</label>
                                <input type='text' class='form-control' id='editColor' required>
                            </div>
                            <div class='col-md-6 mb-3'>
                                <label for='editLcp' class='form-label'>LCP</label>
                                <input type='number' step='0.01' class='form-control' id='editLcp'>
                            </div>
                        </div>
                        <div class='row'>
                            <div class='col-md-6 mb-3'>
                                <label for='editCurrentBranch' class='form-label'>Branch</label>
                                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') { ?>
                                    <!-- Admin can select any branch -->
                                    <select class='form-select' id='editCurrentBranch' required>
                                        <option value='HEADOFFICE'>Head Office</option>
                                        <option value='RXS-S'>RXS-S</option>
                                        <option value='RXS-H'>RXS-H</option>
                                        <option value='ANT-1'>ANT-1</option>
                                        <option value='ANT-2'>ANT-2</option>
                                        <option value='SDH'>SDH</option>
                                        <option value='SDS'>SDS</option>
                                        <option value='JAR-1'>JAR-1</option>
                                        <option value='JAR-2'>JAR-2</option>
                                        <option value='SKM'>SKM</option>
                                        <option value='SKS'>SKS</option>
                                        <option value='ALTA'>ALTA</option>
                                        <option value='EMAP'>EMAP</option>
                                        <option value='CUL'>CUL</option>
                                        <option value='BAC'>BAC</option>
                                        <option value='PAS-1'>PAS-1</option>
                                        <option value='PAS-2'>PAS-2</option>
                                        <option value='BAL'>BAL</option>
                                        <option value='GUIM'>GUIM</option>
                                        <option value='PEMDI'>PEMDI</option>
                                        <option value='EEM'>EEM</option>
                                        <option value='AJU'>AJU</option>
                                        <option value='BAIL'>BAIL</option>
                                        <option value='3SMB'>MINDORO MB</option>
                                        <option value='3SMIN'>MINDORO 3S</option>
                                        <option value='MAN'>MANSALAY</option>
                                        <option value='K-RIDERS'>K-RIDERS</option>
                                        <option value='IBAJAY'>IBAJAY</option>
                                        <option value='NUMANCIA'>NUMANCIA</option>
                                        <option value='HEADOFFICE'>HEADOFFICE</option>
                                        <option value='CEBU'>CEBU</option>
                                    </select>
                                <?php } else { ?>
                                    <input type='text' class='form-control' id='editCurrentBranch' value="<?php echo $_SESSION['user_branch']; ?>" readonly>
                                    <input type='hidden' id='editCurrentBranchHidden' value="<?php echo $_SESSION['user_branch']; ?>">
                                <?php } ?>
                            </div>
                           <div class='col-md-6 mb-3'>
    <label for='editStatus' class='form-label'>Status</label>
    <select class='form-select' id='editStatus' disabled>
        <option value='available'>Available</option>
        <option value='sold'>Sold</option>
        <option value='transferred'>Transferred</option>
    </select>
</div>

                        </div>
                        <div class='d-grid'>
                            <button type='submit' class='btn btn-primary text-white'>Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Transfer Motorcycle Modal -->
    <div class='modal fade' id='transferMotorcycleModal' tabindex='-1' aria-labelledby='transferMotorcycleModalLabel'
        aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header'>
                    <h5 class='modal-title' id='transferMotorcycleModalLabel'>Transfer Motorcycle</h5>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body'>
                    <form id='transferMotorcycleForm'>
                        <input type='hidden' id='transferId'>
                        <div class='mb-3'>
                            <label for='fromBranch' class='form-label'>From Branch</label>
                            <input type='text' class='form-control' id='fromBranch' readonly>
                        </div>
                        <div class='mb-3'>
                            <label for='toBranch' class='form-label'>To Branch</label>
                            <select class='form-select' id='toBranch' required>
                                <option value='HEADOFFICE'>Head Office</option>
                                <option value='RXS-S'>RXS-S</option>
                                <option value='RXS-H'>RXS-H</option>
                                <option value='ANT-1'>ANT-1</option>
                                <option value='ANT-2'>ANT-2</option>
                                <option value='SDH'>SDH</option>
                                <option value='SDS'>SDS</option>
                                <option value='JAR-1'>JAR-1</option>
                                <option value='JAR-2'>JAR-2</option>
                                <option value='SKM'>SKM</option>
                                <option value='SKS'>SKS</option>
                                <option value='ALTA'>ALTA</option>
                                <option value='EMAP'>EMAP</option>
                                <option value='CUL'>CUL</option>
                                <option value='BAC'>BAC</option>
                                <option value='PAS-1'>PAS-1</option>
                                <option value='PAS-2'>PAS-2</option>
                                <option value='BAL'>BAL</option>
                                <option value='GUIM'>GUIM</option>
                                <option value='PEMDI'>PEMDI</option>
                                <option value='EEM'>EEM</option>
                                <option value='AJU'>AJU</option>
                                <option value='BAIL'>BAIL</option>
                                <option value='3SMB'>MINDORO MB</option>
                                <option value='3SMIN'>MINDORO 3S</option>
                                <option value='MAN'>MANSALAY</option>
                                <option value='K-RIDERS'>K-RIDERS</option>
                                <option value='IBAJAY'>IBAJAY</option>
                                <option value='NUMANCIA'>NUMANCIA</option>
                                <option value='HEADOFFICE'>HEADOFFICE</option>
                                <option value='CEBU'>CEBU</option>
                            </select>
                        </div>
                        <div class='mb-3'>
                            <label for='transferDate' class='form-label'>Transfer Date</label>
                            <input type='date' class='form-control' id='transferDate' required>
                        </div>
                        <div class='mb-3'>
                            <label for='transferNotes' class='form-label'>Notes</label>
                            <textarea class='form-control' id='transferNotes' rows='3'></textarea>
                        </div>
                        <div class='d-grid'>
                            <button type='submit' class='btn btn-primary text-white'>Transfer Motorcycle</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Incoming Transfers Modal -->
    <div class="modal fade" id="incomingTransfersModal" tabindex="-1" aria-labelledby="incomingTransfersModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white" id="incomingTransfersModalLabel">Incoming Units Transferred to
                        Your Branch</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Model</th>
                                    <th>Engine No.</th>
                                    <th>Frame No.</th>
                                    <th>Color</th>
                                    <th>Transfer Date</th>
                                    <th>From Branch</th>
                                </tr>
                            </thead>
                            <tbody id="incomingTransfersBody">
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success" id="acceptAllTransfersBtn">Accept All</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Multiple Transfer Modal - Improved Layout -->
    <div class="modal fade" id="multipleTransferModal" tabindex="-1" aria-labelledby="multipleTransferModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white" id="multipleTransferModalLabel">
                        <i class="bi bi-truck me-2 text-white"></i>Transfer Multiple Motorcycles
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <form id="multipleTransferForm">
                        <div class="row g-0">
                            <!-- Transfer Details (Left Panel) -->
                            <div class="col-md-4 border-end bg-light">
                                <div class="p-4">
                                    <fieldset>
                                        <legend class="fs-6 fw-semibold text-black mb-4">
                                            <i class="bi bi-geo-alt me-2"></i>Transfer Information
                                        </legend>

                                        <div class="mb-3">
                                            <label for="multipleFromBranch" class="form-label small fw-semibold">
                                                <i class="bi bi-geo-alt me-1"></i>From Branch
                                            </label>
                                            <input type="text" class="form-control form-control-sm"
                                                id="multipleFromBranch" readonly>
                                        </div>

                                        <div class="mb-3">
                                            <label for="multipleToBranch" class="form-label small fw-semibold">
                                                <i class="bi bi-geo-alt-fill me-1"></i>To Branch <span
                                                    class="text-danger">*</span>
                                            </label>
                                            <select class="form-select form-select-sm" id="multipleToBranch" required>
                                                <option value="">Select Destination Branch</option>

                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="multipleTransferDate" class="form-label small fw-semibold">
                                                <i class="bi bi-calendar me-1"></i>Transfer Date <span
                                                    class="text-danger">*</span>
                                            </label>
                                            <input type="date" class="form-control form-control-sm"
                                                id="multipleTransferDate" required>
                                        </div>

                                        <div class="mb-4">
                                            <label for="multipleTransferNotes" class="form-label small fw-semibold">
                                                <i class="bi bi-chat-text me-1"></i>Transfer Notes
                                            </label>
                                            <textarea class="form-control form-control-sm" id="multipleTransferNotes"
                                                rows="3" placeholder="Optional notes about this transfer..."></textarea>
                                        </div>
                                    </fieldset>

                                    <hr>

                                    <!-- Transfer Summary Section -->
                                    <fieldset>
                                        <legend class="fs-6 fw-semibold text-black mb-3">
                                            <i class="bi bi-calculator me-2"></i>Transfer Summary
                                        </legend>

                                        <div class="summary-card p-3 mb-3"
                                            style="background: white; border-radius: 8px; border: 1px solid #e9ecef;">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="small fw-semibold">Total Units:</span>
                                                <span class="badge bg-primary" id="selectedCount">0</span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="small fw-semibold">Total LCP Value:</span>
                                                <span class="fw-bold text-success" id="totalLcpValue">₱0.00</span>
                                            </div>
                                        </div>

                                        <div class="progress mb-4" style="height: 6px;">
                                            <div class="progress-bar" id="selectionProgress" style="width: 0%"></div>
                                        </div>

                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-success btn-sm">
                                                <i class="bi bi-truck me-2"></i>Transfer Selected Motorcycles
                                            </button>
                                        </div>
                                    </fieldset>
                                </div>
                            </div>

                            <!-- Motorcycle Selection (Right Panel) -->
                            <div class="col-md-8">
                                <div class="p-4">
                                    <h6 class="fw-semibold text-primary mb-4">
                                        <i class="bi bi-search me-2"></i>Motorcycle Selection
                                    </h6>

                                    <!-- Search Form -->
                                    <div class="row g-2 mb-3 align-items-end">
                                        <div class="col-md-8">
                                            <label class="form-label small fw-semibold">
                                                <i class="bi bi-upc-scan me-1"></i>Search by Engine Number
                                            </label>
                                            <input type="text" class="form-control form-control-sm" id="engineSearch"
                                                placeholder="Enter engine number...">
                                        </div>
                                        <div class="col-md-4">
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-primary btn-sm w-100 text-white" type="button"
                                                    id="searchEngineBtn">
                                                    <i class="bi bi-search me-1"></i>Search
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-text small text-muted mb-4">You can search using full or partial
                                        engine numbers.</div>

                                    <!-- Search & Selected Results Panels -->
                                    <div class="row g-3">
                                        <!-- Search Results -->
                                        <div class="col-md-6">
                                            <div class="card h-100 shadow-sm">
                                                <div
                                                    class="card-header py-2 bg-light d-flex justify-content-between align-items-center">
                                                    <span class="fw-semibold small">
                                                        <i class="bi bi-list-check me-1"></i>Search Results
                                                    </span>
                                                    <span class="badge bg-secondary" id="searchResultsCount">0</span>
                                                </div>
                                                <div class="card-body p-0">
                                                    <div class="search-results-container"
                                                        style="max-height: 300px; overflow-y: auto;" id="searchResults">
                                                        <div class="text-center text-muted py-5">
                                                            <i class="bi bi-search display-6 mb-2"></i>
                                                            <p class="small">Search for motorcycles to display results
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Selected Items -->
                                        <div class="col-md-6">
                                            <div class="card h-100 shadow-sm">
                                                <div
                                                    class="card-header py-2 bg-light d-flex justify-content-between align-items-center">
                                                    <span class="fw-semibold small">
                                                        <i class="bi bi-check-circle me-1"></i>Selected Items
                                                    </span>
                                                    <button type="button"
                                                        class="btn btn-outline-danger btn-sm py-0 px-2"
                                                        id="clearSelectionBtn" title="Clear All">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                                <div class="card-body p-0">
                                                    <div class="selected-items-container"
                                                        style="max-height: 300px; overflow-y: auto;"
                                                        id="selectedMotorcyclesList">
                                                        <div class="text-center text-muted py-5">
                                                            <i class="bi bi-inbox display-6 mb-2"></i>
                                                            <p class="small">No motorcycles selected</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div> <!-- End row -->
                                </div> <!-- End p-4 -->
                            </div> <!-- End Right Panel -->
                        </div> <!-- End row -->
                    </form>
                </div> <!-- End modal-body -->
            </div> <!-- End modal-content -->
        </div> <!-- End modal-dialog -->
    </div>

    <!-- Sell Motorcycle Modal -->
<div class="modal fade" id="sellMotorcycleModal" tabindex="-1" aria-labelledby="sellMotorcycleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sellMotorcycleModalLabel">Mark Motorcycle as Sold</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="saleForm">
                    <input type="hidden" id="sellMotorcycleId">
                    
                    <div class="mb-3">
                        <label for="saleDate" class="form-label">Sale Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="saleDate" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="customerName" class="form-label">Customer Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="customerName" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="paymentType" class="form-label">Payment Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="paymentType" onchange="handlePaymentTypeChange()" required>
                            <option value="">Select Payment Type</option>
                            <option value="COD">Cash on Delivery (COD)</option>
                            <option value="Installment">Installment</option>
                        </select>
                    </div>
                    
                    <!-- COD Fields -->
                    <div id="codFields" style="display: none;">
                        <div class="mb-3">
                            <label for="drNumber" class="form-label">DR Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="drNumber">
                        </div>
                        
                        <div class="mb-3">
                            <label for="codAmount" class="form-label">COD Amount <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="codAmount">
                        </div>
                    </div>
                    
                    <!-- Installment Fields -->
                    <div id="installmentFields" style="display: none;">
                        <div class="mb-3">
                            <label for="terms" class="form-label">Terms (months) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="terms" min="1">
                        </div>
                        
                        <div class="mb-3">
                            <label for="monthlyAmortization" class="form-label">Monthly Amortization <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="monthlyAmortization">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitSale()">Mark as Sold</button>
            </div>
        </div>
    </div>
</div>
       <!-- Receipt Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title text-white" id="receiptModalLabel">
                    <i class="bi bi-check-circle me-2"></i>Transfer Receipt
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="receiptContent" class="receipt-container">
                    <!-- Receipt content will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary text-white" id="printReceiptBtn">
                    <i class="bi bi-printer me-2"></i>Print Receipt
                </button>
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
                    <h5 class="modal-title" id="confirmationModalLabel">Confirm Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="confirmationMessage"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary text-white" id="confirmActionBtn">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <i class="bi bi-check-circle-fill text-success fs-1"></i>
                    <p id="successMessage" class="mt-3"></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Modal -->
    <div class="modal fade" id="errorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <i class="bi bi-x-circle-fill text-danger fs-1"></i>
                    <p id="errorMessage" class="mt-3"></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Warning Modal -->
    <div class="modal fade" id="warningModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <i class="bi bi-exclamation-triangle-fill text-warning fs-1"></i>
                    <p id="warningMessage" class="mt-3"></p>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
    <script src="https://printjs-4de6.kxcdn.com/print.min.js"></script>

    <script>
    const currentBranch = '<?php echo $_SESSION['user_branch'] ?? 'RXS-S'; ?>';
    const currentUserBranch = "<?php echo isset($_SESSION['user_branch']) ? $_SESSION['user_branch'] : ''; ?>";
    const currentUserPosition = "<?php echo isset($_SESSION['position']) ? $_SESSION['position'] : ''; ?>";
    const isHeadOffice = currentUserBranch === 'HEADOFFICE';
    const isAdminUser = ['ADMIN', 'IT STAFF', 'HEAD'].includes(currentUserPosition.toUpperCase());
    </script>
    <script src="../js/inventory_management.js"></script>
</body>

</html>