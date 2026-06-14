<?php
session_start();
if (!isset($_SESSION['username']) || !in_array($_SESSION['position'], ['Spareparts-Admin', 'Spareparts-Owner'])) {
    header('Location: ../login.html');
    exit();
}
$username = $_SESSION['username'];
$backLink = ($_SESSION['position'] === 'Spareparts-Owner') ? 'owner_dashboard.php' : 'admin_dashboard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Spareparts MS</title>
    <link rel="icon" href="../assets/img/smdi_logosmall.png" type="image/png">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/spareparts_premium.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-dark: #004d40;
            --accent-green: #26a69a;
        }

        .navbar-custom {
            background-color: var(--primary-dark) !important;
            padding: 0.75rem 1rem;
        }

        .navbar-brand {
            font-size: 1.1rem;
            letter-spacing: 0.5px;
            font-weight: 700 !important;
        }

        .navbar-custom .back-btn {
            color: white !important;
            font-size: 1.25rem;
            margin-right: 1rem;
            text-decoration: none;
            transition: opacity 0.2s;
        }

        .user-profile {
            display: flex;
            align-items: center;
            color: white;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
        }

        .btn-logout {
            border: 1px solid rgba(255,255,255,0.5);
            color: white;
            background: transparent;
            padding: 4px 15px;
            border-radius: 20px;
            font-size: 0.75rem;
            text-decoration: none;
            transition: all 0.2s;
            font-weight: 600;
        }
        
        .main-container {
            margin-top: 80px;
            padding: 20px;
        }

        /* Specific fix for modal white space */
        #userModal .modal-content {
            padding: 0 !important;
            border: none !important;
        }
        
        #userModal .modal-header {
            margin: 0 !important;
            border-bottom: none !important;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom fixed-top shadow-sm">
        <div class="container-fluid">
                <a class="navbar-brand text-uppercase" href="#">
                    <img src="../assets/img/rcsm_logo.jpg" height="30" class="me-2">
                    User Management
                </a>
            <div class="ms-auto d-flex align-items-center">
                <div class="user-profile me-3">
                    <i class="bi bi-person-gear"></i>
                    <span>USER: <?php echo htmlspecialchars($username); ?></span>
                </div>
                <a href="../api/logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </nav>

    <div class="main-container">
        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
            <div class="d-flex align-items-center gap-3">
                <a href="<?php echo $backLink; ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
                <h4 class="mb-0 fw-bold"><i class="bi bi-people me-2"></i>Spareparts Users</h4>
            </div>
            <div class="d-flex gap-3 align-items-center">
                <button class='btn btn-primary fw-bold px-3 shadow-sm text-white' onclick="openAddUserModal()">
                    <i class='bi bi-person-plus me-1'></i>Add User
                </button>
                <div class="input-group" style="width: 250px;">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                    <input type='text' id='userSearchInput' class='form-control border-start-0' placeholder='Search by Name, Username, Job or Branch...'>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted">
                            <tr>
                                <th class="ps-4">Username</th>
                                <th>Full Name</th>
                                <th>Role / Position</th>
                                <th>Branch</th>
                                <th class="text-center pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            <tr><td colspan="5" class="text-center py-5">Loading users...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white border-top-0 py-3">
                    <div id="paginationContainer" class="d-flex justify-content-between align-items-center small text-muted px-3">
                        <span id="pageInfo">Showing 0 of 0 users</span>
                        <div class="btn-group">
                            <button id="prevPageBtn" class="btn btn-sm btn-outline-primary text-white">Previous</button>
                            <button id="nextPageBtn" class="btn btn-sm btn-outline-primary text-white">Next</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- User Modal -->
    <div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <form id="userForm">
                    <input type="hidden" id="userId" name="id">
                    <div class="modal-header bg-dark text-white">
                        <h5 class="modal-title fw-bold" id="userModalTitle"><i class="bi bi-person-plus me-2"></i>Add User</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-5 bg-light">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted small">Username *</label>
                            <input type="text" class="form-control" id="formUsername" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted small">Full Name</label>
                            <input type="text" class="form-control" id="formFullName" name="fullName">
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-bold text-muted small">Position / Role *</label>
                                <select class="form-select" id="formPosition" name="position" required>
                                    <option value="Spareparts-Sales">Spareparts Sales</option>
                                    <option value="Spareparts-Warehouse">Spareparts Warehouse</option>
                                    <option value="Spareparts-Admin">Spareparts Admin</option>
                                    <option value="Spareparts-Owner">Spareparts Owner</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold text-muted small">Branch</label>
                                <input type="text" class="form-control" id="formBranch" name="branch" value="HEADOFFICE">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted small">Report Header Title (Custom PDF/Print Header)</label>
                            <input type="text" class="form-control" id="formReportHeaderTitle" name="report_header_title" placeholder="e.g. ROXAS CITY SOLID MERCHANDISING - BRANCH X">
                        </div>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label fw-bold text-muted small">Password</label>
                                <input type="password" class="form-control" id="formPassword" name="password" placeholder="Leave empty to keep">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold text-muted small">Confirm Password</label>
                                <input type="password" class="form-control" id="formConfirmPassword" name="confirmPassword">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-premium-cancel" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-premium-save" id="saveUserBtn">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="user_management.js"></script>
</body>
</html>
