<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>SMDI - SALES | The Highest Levels of Service</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">
    
    <link rel="icon" href="img/smdi_logosmall.png" type="image/png">

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
    <script src="js/script.js"></script>

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
    pointer-events: none; /* Disable click */
    background-color: white;
    border-color: #ccc;
}

.pagination .page-item.disabled .page-link:hover {
    background-color: white;
    color: #ccc; /* No change on hover for disabled */
}

    </style>
</head>

<body>
    <!-- Navbar-->
    <div class="container-fluid fixed-top">
        <div class="container topbar bg-primary d-none d-lg-block">
            <div class="d-flex justify-content-between">
                <div class="top-info ps-2">
                    <small class="me-3"><i class="fas fa-map-marker-alt me-2 text-primary"></i> <a href="#" class="text-white">1031, Victoria Building, Roxas Avenue, Roxas City, 5800</a></small>
                </div>
                <div class="top-link pe-2"></div>
            </div>
        </div>
        <div class="container px-0">
            <nav class="navbar navbar-light bg-white navbar-expand-lg">
                <a href="index.html" class="navbar-brand">
                    <img src="img/smdi_logo.jpg" alt="Company Logo" class="logo">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
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
    <!-- Navbar-->

    <!-- Main Container -->
    <div class="container-fluid py-5" style="margin-top: 120px;">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Sales Records</h5>
                <button class="btn btn-primary text-white mb-3" data-bs-toggle="modal" data-bs-target="#addSaleModal">Add New Sale</button>
                <button class="btn btn-primary text-white mb-3" data-bs-toggle="modal" data-bs-target="#printOptionsModal">Print Documents</button>
                <button id="deleteSelectedButton" class="btn btn-primary text-white mb-3">Delete Selected</button>
                
                <!-- Search and Sort Options -->
                <div class="mb-3 d-flex">
                    <input type="text" id="searchInput" class="form-control me-2" placeholder="Search...">
                    <div class="dropdown">
                        <button class="btn btn-primary text-white dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            Sort by
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="sortDropdown">
                            <li><a class="dropdown-item" href="#" data-sort="date">Date</a></li>
                            <li><a class="dropdown-item" href="#" data-sort="branch">Branch</a></li>
                            <li><a class="dropdown-item" href="#" data-sort="brand">Brand</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Table of Records -->
                <table id="salesTable" class="table table-striped">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>Date</th>
                            <th>Branch</th>
                            <th>Brand</th>
                            <th>Model</th>
                            <th>Quantity</th>
                            <th class="no-print">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="salesTableBody">
                        <!-- Sales records will be loaded here by AJAX -->
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

    <!-- Print Options Modal -->
    <div class="modal fade" id="printOptionsModal" tabindex="-1" aria-labelledby="printOptionsModalLabel" aria-hidden="true">
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
                                <option value="dateRange">Date Range</option>
                                <option value="branch">Branch</option>
                            </select>
                        </div>
                        <div class="mb-3" id="dateRange" style="display: none;">
                            <label for="fromDate" class="form-label">From:</label>
                            <input type="date" class="form-control" id="fromDate">
                            <label for="toDate" class="form-label">To:</label>
                            <input type="date" class="form-control" id="toDate">
                        </div>
                        <div class="mb-3" id="branchSelection" style="display: none;">
                            <label for="branchSelect" class="form-label">Select Branch:</label>
                            <select class="form-select" id="branchSelect">
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

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    $(document).ready(function() {
        let selectedRecordIds = [];
        let saleIdToDelete = null;
        let currentPage = 1;

        // Define the models for each brand
        const brandModels = {
            "Suzuki": ["GSX-250RL/FRLX", "GSX-150", "BIGBIKE", "GSX150FRF NEW", "GSX-S150", "UX110NER", "UB125", "AVENIS", "FU150", "FU150-FI", "FW110D", "FW110SD/SC", "DS250RL", "FJ110 LB-2", "FW110D(SMASH FI)", "FJ110LX", "UB125LNM(NEW)", "UK110", "UX110", "UK125", "GD110"],
            "Honda": ["GIORNO+", "CCG 125", "CFT125MRCS", "AFB110MDJ", "AFS110MDJ", "AFB110MDH", "CFT125MSJ", "AFS110MCDE", "MRCP", "DIO", "MSM", "MRP", "MRS", "CFT125MRCJ", "MSP", "MSS", "AFP110DFP", "MRCP", "AFP110DFR", "ZN125", "PCX160NEW", "PCX160", "AFB110MSJ", "AFP110SFR", "AFP110SFP", "CBR650", "CB500", "CB650R", "GL150R", "CBR500", "AIRBLADE 150", "AIRBLADE160", "ADV160", "CBR150RMIV/RAP", "BEAT-CSFN/FR/R3/FS/3", "CB150X", "WINNER X", "CRF-150", "CRF300", "CMX500", "XR150", "ACB160", "ACB125"],
            "Yamaha": ["MIO SPORTY", "MIOI125", "MIO GEAR", "SNIPER", "MIO GRAVIS", "YTX", "YZF R3", "FAZZIO", "XSR", "VEGA", "AEROX", "XTZ", "NMAX", "PG-1 BRN1", "MT-15", "FZ", "R15M BNE1/2", "XMAX", "WR155", "SEROW"],
            "Kawasaki": ["CT100 A", "CT100B", "CT125", "CA100AA NEW", "BC175H/MS", "BC175J/NN/SN", "BC175 III ELECT.", "BC175 III KICK", "BRUSKY", "NS125", "ELIMINATOR SE", "CT100B", "NINJA ZX 4RR", "Z900 SE", "KLX140", "KLX150", "CT150BA", "ROUSER 200", "W800", "VERYS 650", "KLX232", "NINJA ZX-10R", "Z900 SE"]
        };

        // Handle brand change to update models dropdown
        $('#brand').change(function() {
            updateModelsDropdown($(this).val(), $('#model'));
        });

        // Handle edit brand change
        $('#editBrand').change(function() {
            updateModelsDropdown($(this).val(), $('#editmodel'));
        });

        function updateModelsDropdown(brand, $dropdown) {
            $dropdown.empty();
            if (brand && brandModels[brand]) {
                brandModels[brand].forEach(model => {
                    $dropdown.append($('<option>', {
                        value: model,
                        text: model
                    }));
                });
            }
        }

        // Initialize models dropdown on page load
        updateModelsDropdown($('#brand').val(), $('#model'));

        function loadSales(query = '', page = currentPage, sort = '') {
            $.ajax({
                url: 'api/sales_data_management.php',
                method: 'GET',
                data: { 
                    action: 'get_sales',
                    query: query,
                    page: page,
                    sort: sort
                },
                success: function(response) {
                    if (response.success) {
                        $('#salesTableBody').html(generateTableRows(response.data));
                        updatePaginationControls(response.totalPages);
                    } else {
                        showErrorModal(response.message || 'Failed to load sales data');
                    }
                },
                error: function(xhr, status, error) {
                    showErrorModal('Error loading sales: ' + error);
                }
            });
        }

        function generateTableRows(sales) {
            let rows = '';
            sales.forEach(sale => {
                rows += `
                    <tr data-id="${sale.id}">
                        <td><input type="checkbox" name="recordCheckbox" value="${sale.id}"></td>
                        <td>${new Date(sale.sales_date).toLocaleDateString()}</td>
                        <td>${sale.branch}</td>
                        <td>${sale.brand}</td>
                        <td>${sale.model}</td>
                        <td>${sale.qty}</td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <button class="btn btn-outline-primary edit-button">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-outline-danger delete-button">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
            return rows;
        }

        function updatePaginationControls(totalPages) {
            let paginationHtml = '';
            
            // Previous button
            paginationHtml += `
                <li id="prevPage" class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a>
                </li>
            `;
            
            // Page numbers
            for (let i = 1; i <= totalPages; i++) {
                paginationHtml += `
                    <li class="page-item ${currentPage === i ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `;
            }
            
            // Next button
            paginationHtml += `
                <li id="nextPage" class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#">Next</a>
                </li>
            `;
            
            $('#paginationControls').html(paginationHtml);
        }

        // Initial load of sales
        loadSales();

        // Search input event
        $('#searchInput').on('input', function() {
            const query = $(this).val();
            currentPage = 1; // Reset to first page when searching
            loadSales(query);
        });

        // Pagination click events
        $(document).on('click', '.page-link', function(e) {
            e.preventDefault();
            if ($(this).parent().hasClass('disabled')) return;
            
            if ($(this).attr('id') === 'prevPage') {
                currentPage--;
            } else if ($(this).attr('id') === 'nextPage') {
                currentPage++;
            } else {
                currentPage = parseInt($(this).data('page'));
            }
            
            loadSales($('#searchInput').val(), currentPage);
        });

        // Sorting functionality
        $('.dropdown-item').on('click', function(e) {
            e.preventDefault();
            const sortOption = $(this).data('sort');
            currentPage = 1; // Reset to first page when sorting
            loadSales($('#searchInput').val(), currentPage, sortOption);
        });

        // Handle add sale form submission
        $('#addSaleForm').submit(function(e) {
            e.preventDefault();
            
            const formData = {
                action: 'add_sale',
                sales_date: $('#saleDate').val(),
                branch: $('#branch').val(),
                brand: $('#brand').val(),
                model: $('#model').val(),
                qty: $('#quantity').val()
            };
            
            $.ajax({
                url: 'api/sales_data_management.php',
                method: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#addSaleModal').modal('hide');
                        $('#addSaleForm')[0].reset();
                        loadSales();
                        showSuccessModal('Sale added successfully!');
                    } else {
                        if (response.message.includes('duplicate')) {
                            showDuplicateErrorModal(response.message);
                        } else {
                            showErrorModal(response.message || 'Failed to add sale');
                        }
                    }
                },
                error: function(xhr, status, error) {
                    showErrorModal('Error: ' + error);
                }
            });
        });

        // Handle edit button click
        $(document).on('click', '.edit-button', function() {
            const saleId = $(this).closest('tr').data('id');
            
            $.ajax({
                url: 'api/sales_data_management.php',
                method: 'GET',
                data: { action: 'get_sale', id: saleId },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data) {
                        const sale = response.data;
                        $('#editSaleId').val(sale.id);
                        $('#editSaleDate').val(sale.sales_date);
                        $('#editBranch').val(sale.branch);
                        $('#editBrand').val(sale.brand);
                        $('#editmodel').val(sale.model);
                        $('#editQuantity').val(sale.qty);
                        
                        // Update models dropdown for the selected brand
                        updateModelsDropdown(sale.brand, $('#editmodel'));
                        $('#editmodel').val(sale.model);
                        
                        $('#editSaleModal').modal('show');
                    } else {
                        showErrorModal(response.message || 'Failed to load sale data');
                    }
                },
                error: function(xhr, status, error) {
                    showErrorModal('Error loading sale: ' + error);
                }
            });
        });

        // Handle edit sale form submission
        $('#editSaleForm').submit(function(e) {
            e.preventDefault();
            
            const formData = {
                action: 'update_sale',
                id: $('#editSaleId').val(),
                sales_date: $('#editSaleDate').val(),
                branch: $('#editBranch').val(),
                brand: $('#editBrand').val(),
                model: $('#editmodel').val(),
                qty: $('#editQuantity').val()
            };
            
            $.ajax({
                url: 'api/sales_data_management.php',
                method: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#editSaleModal').modal('hide');
                        loadSales();
                        showSuccessModal('Sale updated successfully!');
                    } else {
                        showErrorModal(response.message || 'Failed to update sale');
                    }
                },
                error: function(xhr, status, error) {
                    showErrorModal('Error updating sale: ' + error);
                }
            });
        });

        // Handle delete button click
        $(document).on('click', '.delete-button', function() {
            saleIdToDelete = $(this).closest('tr').data('id');
            $('#confirmationModal').modal('show');
        });

        // Handle delete selected button click
        $('#deleteSelectedButton').on('click', function() {
            if (selectedRecordIds.length > 0) {
                $('#confirmationModal').modal('show');
            } else {
                showWarningModal('No sales selected for deletion.');
            }
        });

        // Handle checkbox changes
        $('#salesTableBody').on('change', 'input[name="recordCheckbox"]', function() {
            updateSelectedRecords();
        });

        // Handle select all checkbox
        $('#selectAll').on('change', function() {
            const isChecked = $(this).is(':checked');
            $('#salesTableBody input[name="recordCheckbox"]').prop('checked', isChecked);
            updateSelectedRecords();
        });

        function updateSelectedRecords() {
            selectedRecordIds = [];
            $('#salesTableBody input[name="recordCheckbox"]:checked').each(function() {
                selectedRecordIds.push($(this).val());
            });
        }

        // Handle confirm delete button click
        $('#confirmDeleteBtn').on('click', function() {
            const idsToDelete = saleIdToDelete ? [saleIdToDelete] : selectedRecordIds;
            
            if (idsToDelete.length > 0) {
                $.ajax({
                    url: 'api/sales_data_management.php',
                    method: 'POST',
                    data: { 
                        action: 'delete_sale',
                        ids: idsToDelete
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#confirmationModal').modal('hide');
                            loadSales();
                            showSuccessModal(response.message || 'Sales deleted successfully!');
                        } else {
                            showErrorModal(response.message || 'Failed to delete sales');
                        }
                    },
                    error: function(xhr, status, error) {
                        showErrorModal('Error deleting sales: ' + error);
                    },
                    complete: function() {
                        saleIdToDelete = null;
                        selectedRecordIds = [];
                        $('#selectAll').prop('checked', false);
                    }
                });
            }
        });

        // Print options functionality
        $('#sortBy').on('change', function() {
            const selectedValue = $(this).val();
            
            // Hide all range selectors
            $('#dateRange').hide();
            $('#branchSelection').hide();
            
            // Show the appropriate range based on the selected value
            if (selectedValue === 'dateRange') {
                $('#dateRange').show();
            } else if (selectedValue === 'branch') {
                $('#branchSelection').show();
            }
        });

        // Handle print confirmation
        $('#confirmPrint').on('click', function() {
            const documentType = $('#documentType').val();
            const sortBy = $('#sortBy').val();
            const fromDate = $('#fromDate').val();
            const toDate = $('#toDate').val();
            const branch = $('#branchSelect').val();
            const outputFormat = $('#outputFormat').val();

            // Validate inputs based on selected options
            if (sortBy === 'dateRange' && (!fromDate || !toDate)) {
                showWarningModal('Please enter both From and To dates.');
                return;
            }

            if (sortBy === 'branch' && !branch) {
                showWarningModal('Please select a branch.');
                return;
            }

            // Build the URL with parameters
            let url = `api/generate_sales_pdf.php?documentType=${encodeURIComponent(documentType)}&sortBy=${encodeURIComponent(sortBy)}&outputFormat=${encodeURIComponent(outputFormat)}`;
            
            if (sortBy === 'dateRange') {
                url += `&fromDate=${encodeURIComponent(fromDate)}&toDate=${encodeURIComponent(toDate)}`;
            } else if (sortBy === 'branch') {
                url += `&branch=${encodeURIComponent(branch)}`;
            }

            // Open the URL in a new window
            window.open(url, '_blank');
            $('#printOptionsModal').modal('hide');
        });

        // Modal helper functions
        function showSuccessModal(message) {
            $('#successMessage').text(message);
            $('#successModal').modal('show');
            setTimeout(() => {
                $('#successModal').modal('hide');
            }, 2000);
        }

        function showErrorModal(message) {
            $('#errorMessage').text(message);
            $('#errorMessage').show();
            setTimeout(() => {
                $('#errorMessage').hide();
            }, 3000);
        }

        function showDuplicateErrorModal(message) {
            $('#duplicateErrorMessage').text(message);
            $('#duplicateErrorModal').modal('show');
        }

        function showWarningModal(message) {
            $('#warningMessage').text(message);
            $('#warningModal').modal('show');
        }
    });
    </script>
</body>
</html>