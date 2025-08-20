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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://printjs-4de6.kxcdn.com/print.min.js"></script>
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
                    <img src="../assets/img/smdi_logo.jpg" alt="SMDI Logo" class="logo">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse"
                    aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarCollapse">
                    <div class="navbar-nav">
                        <a href="staffDashboard.html" class="nav-item nav-link active">Home</a>
                       
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
                        <button class="nav-link active" id="dashboard-tab" data-bs-toggle="tab"
                            data-bs-target="#dashboard" type="button" role="tab">Dashboard</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="management-tab" data-bs-toggle="tab" data-bs-target="#management"
                            type="button" role="tab">Inventory Management</button>
                    </li>
                </ul>

                <div class="tab-content" id="inventoryTabContent">
                    <!-- Dashboard Tab -->
                    <div class="tab-pane fade show active" id="dashboard" role="tabpanel">
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
                                <button id="deleteSelectedBtn" class="btn btn-danger" disabled>
                                    <i class="bi bi-trash"></i> Delete Selected
                                </button>
                                <button id="transferSelectedBtn" class="btn btn-primary text-white" disabled>
            <i class="bi bi-truck"></i> Return Selected
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
                                        <th><input type="checkbox" id="selectAll"></th>
                                        <th class="sortable-header" data-sort="date_delivered">Date Delivered</th>
                                        <th class="sortable-header" data-sort="brand">Brand</th>
                                        <th class="sortable-header" data-sort="model">Model</th>
                                        <th>Engine No.</th>
                                        <th>Frame No.</th>
                                        <th>Color</th>
                                        <th>LCP</th>
                                        <th class="sortable-header" data-sort="current_branch">Current Branch</th>
                                        <th class="no-print">Actions</th>
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
    <div class="modal fade" id="addMotorcycleModal" tabindex="-1" aria-labelledby="addMotorcycleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addMotorcycleModalLabel">Add Motorcycle to Inventory</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addMotorcycleForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="dateDelivered" class="form-label">Date Delivered</label>
                                <input type="date" class="form-control" id="dateDelivered" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="brand" class="form-label">Brand</label>
                                <select class="form-select" id="brand" required>
                                    <option value="">Select Brand</option>
                                    <option value="Suzuki">Suzuki</option>
                                    <option value="Honda">Honda</option>
                                    <option value="Kawasaki">Kawasaki</option>
                                    <option value="Yamaha">Yamaha</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="model" class="form-label">Model</label>
                                <input type="text" class="form-control" id="model" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="engineNumber" class="form-label">Engine Number</label>
                                <input type="text" class="form-control" id="engineNumber" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="frameNumber" class="form-label">Frame Number</label>
                                <input type="text" class="form-control" id="frameNumber" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="color" class="form-label">Color</label>
                                <input type="text" class="form-control" id="color" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="quantity" class="form-label">Quantity</label>
                                <input type="number" class="form-control" id="quantity" value="1" min="1" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="lcp" class="form-label">LCP</label>
                                <input type="number" step="0.01" class="form-control" id="lcp">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="currentBranch" class="form-label">Current Branch</label>
                                <select class="form-select" id="currentBranch" required>
    <option value="<?php echo $_SESSION['user_branch']; ?>" selected><?php echo $_SESSION['user_branch']; ?></option>
                                   
                                </select>
                            </div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary text-white">Add Motorcycle</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Motorcycle Modal -->
    <div class="modal fade" id="editMotorcycleModal" tabindex="-1" aria-labelledby="editMotorcycleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editMotorcycleModalLabel">Edit Motorcycle Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editMotorcycleForm">
                        <input type="hidden" id="editId">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editDateDelivered" class="form-label">Date Delivered</label>
                                <input type="date" class="form-control" id="editDateDelivered" required>
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
                                <label for="editModel" class="form-label">Model</label>
                                <input type="text" class="form-control" id="editModel" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editEngineNumber" class="form-label">Engine Number</label>
                                <input type="text" class="form-control" id="editEngineNumber" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editFrameNumber" class="form-label">Frame Number</label>
                                <input type="text" class="form-control" id="editFrameNumber" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editColor" class="form-label">Color</label>
                                <input type="text" class="form-control" id="editColor" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editLcp" class="form-label">LCP</label>
                                <input type="number" step="0.01" class="form-control" id="editLcp">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editCurrentBranch" class="form-label">Current Branch</label>
                                <select class="form-select" id="editCurrentBranch" readonly>
                                    <option value="HEADOFFICE">Head Office</option>
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
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editStatus" class="form-label">Status</label>
                                <select class="form-select" id="editStatus" required>
                                    <option value="available">Available</option>
                                    <option value="sold">Sold</option>
                                    <option value="transferred">Transferred</option>
                                </select>
                            </div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary text-white">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Multiple Transfer Modal -->
<div class="modal fade" id="multipleTransferModal" tabindex="-1" aria-labelledby="multipleTransferModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="multipleTransferModalLabel">Return Multiple Motorcycles</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="multipleTransferForm">
                    <div class="mb-3">
                        <label for="multipleFromBranch" class="form-label">From Branch</label>
                        <input type="text" class="form-control" id="multipleFromBranch" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="multipleToBranch" class="form-label">To Branch</label>
                        <select class="form-select" id="multipleToBranch" readonly>
                            <option value="HEADOFFICE">HEADOFFICE</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="multipleTransferDate" class="form-label">Transfer Date</label>
                        <input type="date" class="form-control" id="multipleTransferDate" required>
                    </div>
                    <div class="mb-3">
                        <label for="multipleTransferNotes" class="form-label">Notes</label>
                        <textarea class="form-control" id="multipleTransferNotes" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Selected Motorcycles (<span id="selectedCount">0</span>)</label>
                        <div class="border p-2" style="max-height: 200px; overflow-y: auto;" id="selectedMotorcyclesList">
                            <!-- Selected motorcycles will be listed here -->
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary text-white">Transfer Motorcycles</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Incoming Transfers Modal -->
<div class="modal fade" id="incomingTransfersModal" tabindex="-1" aria-labelledby="incomingTransfersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title text-white" id="incomingTransfersModalLabel">Incoming Units Transferred to Your Branch</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
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


    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
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
    </script>
    <script src="../js/branch_inventory.js"></script>
</body>
</html>