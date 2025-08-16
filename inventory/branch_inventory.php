<?php include '../api/auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
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
    <div class="container-fluid py-5 mt-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Branch Inventory System</h5>
                    <span class="badge bg-light text-dark">
                        Branch: <?php echo $_SESSION['user_branch'] ?? 'N/A'; ?>
                    </span>
                </div>
            </div>
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

    <!-- View Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Motorcycle Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="motorcycleDetails">
                    <!-- Details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
    <script>
    const currentBranch = '<?php echo $_SESSION['user_branch'] ?? 'RXS-S'; ?>';
</script>
    <script src="../js/branch_inventory.js"></script>
</body>
</html>