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
    padding: 20px 0;
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
                    <img src="../assets/img/smdi_logo.jpg" alt="Company Logo" class="logo">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarCollapse">
                    <div class="navbar-nav">
                        <a href="admin_dashboard.php" class="nav-item nav-link active">Home</a>
                        <a href="../api/logout.php" class="nav-item nav-link active">Logout</a>
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
                <button class="nav-link active" id="records-tab" data-bs-toggle="tab" data-bs-target="#records" type="button" role="tab" aria-controls="records" aria-selected="true">Records</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab" aria-controls="users" aria-selected="false">User Management</button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="adminTabsContent">
            <!-- Records Tab -->
            <div class="tab-pane fade show active" id="records" role="tabpanel" aria-labelledby="records-tab">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Records</h5>
                        <button class="btn btn-primary text-white mb-3" data-bs-toggle="modal" data-bs-target="#addRecordModal">Add New Record</button>
                        <button class="btn btn-primary text-white mb-3" data-bs-toggle="modal" data-bs-target="#printOptionsModal">Print Documents</button>
                        <button id="deleteSelectedButton" class="btn btn-primary text-white mb-3">Delete Selected</button>
                        
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
                        <button class="btn btn-primary text-white mb-3" data-bs-toggle="modal" data-bs-target="#addUserModal">Add New User</button>
                        
                        <!-- Search Users -->
                        <div class="mb-3 d-flex">
                            <input type="text" id="searchUserInput" class="form-control me-2" placeholder="Search users...">
                        </div>

                        <!-- Users Table -->
                        <table id="usersTable" class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
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
<div class="modal fade" id="editRecordModal" tabindex="-1" aria-labelledby="editRecordModalLabel" aria-hidden="true">
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
                            <input type="text" class="form-control" id="editPlateNumber" name="plateNumber" required>
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
                    <label for="nameRange" class="form-label">Ex: A-B = Show All Records with Family Name starting with A only</label>
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

 <!-- Add User Modal -->
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
                            <label for="newUsername" class="form-label">Username</label>
                            <input type="text" class="form-control" id="newUsername" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="newEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="newEmail" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">Password</label>
                            <input type="password" class="form-control" id="newPassword" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                        </div>
                        <div class="mb-3">
                            <label for="userRole" class="form-label">Role</label>
                            <select class="form-select" id="userRole" name="role" required>
                                <option value="admin">Admin</option>
                                <option value="staff" selected>Staff</option>
                            </select>
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
                            <label for="editUsername" class="form-label">Username</label>
                            <input type="text" class="form-control" id="editUsername" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="editEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="editEmail" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="editRole" class="form-label">Role</label>
                            <select class="form-select" id="editRole" name="role" required>
                                <option value="admin">Admin</option>
                                <option value="staff">Staff</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editStatus" class="form-label">Status</label>
                            <select class="form-select" id="editStatus" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editPassword" class="form-label">New Password (leave blank to keep current)</label>
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


</body>

</html>