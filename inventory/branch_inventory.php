<?php include '../api/auth.php';
?>
<!DOCTYPE html>
<html lang='en'>

<head>
    <meta charset='utf-8'>
    <title>SMDI - INVENTORY | The Highest Levels of Service</title>
    <meta content='width=device-width, initial-scale=1.0' name='viewport'>
    <meta content='' name='keywords'>
    <meta content='' name='description'>
    <link rel='icon' href='../assets/img/smdi_logosmall.png' type='image/png'>
    <link rel='stylesheet' href='https://use.fontawesome.com/releases/v5.15.4/css/all.css' />
    <link href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css' rel='stylesheet'>
    <link href='../lib/lightbox/css/lightbox.min.css' rel='stylesheet'>
    <link href='../lib/owlcarousel/assets/owl.carousel.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='https://unpkg.com/leaflet@1.9.3/dist/leaflet.css' />
    <link href='../css/bootstrap.min.css' rel='stylesheet'>
    <link href='../css/style.css' rel='stylesheet'>
    <link href='../css/inventory_style.css' rel='stylesheet'>

    <link rel='stylesheet' href='https://printjs-4de6.kxcdn.com/print.min.css'>
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js'></script>

    <script src='https://code.jquery.com/jquery-3.6.0.min.js'></script>
    <script src='https://printjs-4de6.kxcdn.com/print.min.js'></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>

</head>

<body>

    <div class='container-fluid fixed-top bg-white'>
        <div class='container topbar bg-primary d-none d-lg-block'>
            <div class='d-flex justify-content-between'>
                <div class='top-info ps-2'>
                    <small class='me-3'>
                        <i class='fas fa-map-marker-alt me-2 text-primary'></i>
                        <a href='#' class='text-white'>1031, Victoria Building, Roxas Avenue, Roxas City, 5800</a>
                    </small>
                </div>
                <div class='top-link pe-2'></div>
            </div>
        </div>
        <div class='container px-0'>
            <nav class='navbar navbar-light bg-white navbar-expand-lg'>
                <a class='navbar-brand'>
                    <img src='../assets/img/smdi_logo.jpg' alt='SMDI Logo' class='logo'>
                </a>
                <button class='navbar-toggler' type='button' data-bs-toggle='collapse' data-bs-target='#navbarCollapse'
                    aria-controls='navbarCollapse' aria-expanded='false' aria-label='Toggle navigation'>
                    <span class='navbar-toggler-icon'></span>
                </button>
                <div class='collapse navbar-collapse' id='navbarCollapse'>
                    <div class='navbar-nav'>
                        <a href='../inventory/branch_inventory.php' class='nav-item nav-link active'>Home</a>

                        <a href='../api/logout.php' class='nav-item nav-link active'>Logout</a>

                        <?php if (isset($_SESSION['username'])): ?>
       <span class='nav-item nav-link disabled' style='cursor: default; color: red;'>
    <i class='fas fa-user-circle me-1'></i>
    <?php echo htmlspecialchars($_SESSION['username']); ?>
</span>

    <?php endif; ?>
                    </div>

                    
                </div>
            </nav>
        </div>
    </div>

    <main class='container-fluid py-5' style='margin-top: 110px;'>
        <div class='card mb-4'>
            <div class='card-header bg-white'>
                <h1 class='h5 mb-0'>Motorcycle Inventory Management</h1>
            </div>
            <div class='card-body'>
                <ul class='nav nav-tabs mb-4' id='inventoryTabs' role='tablist'>
                    <li class='nav-item' role='presentation'>
                        <button class='nav-link active' id='inventory-tab' data-bs-toggle='tab'
                            data-bs-target='#inventory' type='button' role='tab'>Overview</button>
                    </li>
                      <li class='nav-item' role='presentation'>
                        <button class='nav-link' id='find-tab' data-bs-toggle='tab'
                            data-bs-target='#find' type='button' role='tab'>Find</button>
                    </li>
                    <li class='nav-item' role='presentation'>
                        <button class='nav-link' id='management-tab' data-bs-toggle='tab' data-bs-target='#management'
                            type='button' role='tab'>Inventory Management</button>
                    </li>
                </ul>
                
                <div class='tab-content' id='inventoryTabContent'>

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
                            <div class='col-12 text-center py-5'>
                                <div class='spinner-border text-primary' role='status'>
                                    <span class='visually-hidden'>Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                 <div class='tab-content' id='findTabContent'>           
<div class="tab-pane fade" id="find" role="tabpanel">
    <div class="container-fluid py-4">
        <div class="row g-4">
            <div class="col-md-12">
                <!-- Search Bar -->
                <div class="search-container mb-4">
                    <div class="input-group">
                        <input type="text" id="searchModel" class="form-control" placeholder="Search motorcycle model...">
                        <button class="btn btn-primary text-white" id="searchModelBtn">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>
                </div>

                <!-- Available Inventory List -->
                <div id="modelList" class="model-list mt-3"></div>
            </div>
        </div>
    </div>
</div>


                    </div>
                        </div>
                <div class='tab-pane fade ' id='management' role='tabpanel'>
                    <div class='d-flex justify-content-between mb-4'>
                        <div>
                            <button class='btn btn-primary text-white me-2' data-bs-toggle='modal'
                                data-bs-target='#addMotorcycleModal'>
                                <i class='bi bi-plus-circle'></i> Add Motorcycle
                            </button>

                            <button id='transferSelectedBtn' class='btn btn-primary text-white' disabled>
                                <i class='bi bi-truck'></i> Transfer
                            </button>
                            <button type="button" class="btn btn-primary text-white me-2" id="generateReportsButton">
                                <i class="bi bi-file-earmark-text"></i> Generate Reports
                            </button>
<button class="btn btn-primary text-white me-2" id="searchTransferReceiptBtn">
    <i class="bi bi-receipt"></i> Search Transfer Receipt
</button>
                        </div>
                        <div class='input-group' style='max-width: 300px;'>
                            <input type='text' id='searchInventory' class='form-control'
                                placeholder='Search inventory...'>
                            <button class='btn btn-primary text-white' type='button' id='searchInventoryBtn'>
                                <i class='bi bi-search'></i>
                            </button>
                        </div>
                    </div>

                    <div class='table-responsive'>
                        <table class='table table-striped' id='inventoryTable'>
                           <thead>
    <tr>
        <th>Invoice No./MT</th>
        <th class='sortable-header' data-sort='date_delivered'>Date Delivered</th>
        <th class='sortable-header' data-sort='brand'>Brand</th>
        <th class='sortable-header' data-sort='model'>Model</th>
        <th class='sortable-header' data-sort='category'>Category</th>
        <th>Engine No.</th>
        <th>Frame No.</th>
        <th>Color</th>
        <th>Inventory Cost</th>
        <th class='sortable-header' data-sort='current_branch'>Current Branch</th>
        <th class='no-print'>Actions</th>
    </tr>
</thead>

                            <tbody id='inventoryTableBody'>
                                <tr>
                                    <td colspan='11' class='text-center py-5'>
                                        <div class='spinner-border text-primary' role='status'>
                                            <span class='visually-hidden'>Loading...</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <nav aria-label='Inventory pagination'>
                        <ul id='paginationControls' class='pagination'>
                            <li id='prevPage' class='page-item disabled'>
                                <a class='page-link' href='#' tabindex='-1' aria-disabled='true'>
                                    <i class='fas fa-chevron-left me-1'></i> Previous
                                </a>
                            </li>
                            <li id='nextPage' class='page-item'>
                                <a class='page-link' href='#'>
                                    Next <i class='fas fa-chevron-right ms-1'></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
        </div>
    </main>

    <div class='modal fade' id='detailsModal' tabindex='-1' aria-labelledby='detailsModalLabel' aria-hidden='true'>
        <div class='modal-dialog modal-lg'>
            <div class='modal-content'>
                <div class='modal-header'>
                    <h5 class='modal-title' id='detailsModalLabel'>Motorcycle Details</h5>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body' id='motorcycleDetails'>
                </div>
                <div class='modal-footer'>
                    <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>
                </div>
            </div>
        </div>
    </div>

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
                                <?php if ( isset( $_SESSION[ 'user_role' ] ) && $_SESSION[ 'user_role' ] === 'admin' ) {
    ?>

                                <select class='form-select' id='branch' required>
                                    <option value='HEADOFFICE'>HEADOFFICE</option>
                                    <option value='KINGDOM'>KINGDOM</option>
                                    <option value='TANQUE'>TANQUE</option>
                                    <option value='ROXAS SUZUKI'>ROXAS SUZUKI</option>
                                    <option value='MAMBUSAO'>MAMBUSAO</option>
                                    <option value='SIGMA'>SIGMA</option>
                                    <option value='PRC'>PRC</option>
                                    <option value='BAILAN'>BAILAN</option>
                                    <option value='CUARTERO'>CUARTERO</option>
                                    <option value='JAMINDAN'>JAMINDAN</option>
                                    <option value='ROXAS HONDA'>ROXAS HONDA</option>
                                    <option value='ANTIQUE-1'>ANTIQUE-1</option>
                                    <option value='ANTIQUE-2'>ANTIQUE-2</option>
                                    <option value='DELGADO HONDA'>DELGADO HONDA</option>
                                    <option value='DELGADO SUZUKI'>DELGADO SUZUKI</option>
                                    <option value='JARO-1'>JARO-1</option>
                                    <option value='JARO-2'>JARO-2</option>
                                    <option value='KALIBO MABINI'>KALIBO MABINI</option>
                                    <option value='KALIBO SUZUKI'>KALIBO SUZUKI</option>
                                    <option value='ALTAVAS'>ALTAVAS</option>
                                    <option value='EMAP'>EMAP</option>
                                    <option value='CULASI'>CULASI</option>
                                    <option value='BACOLOD'>BACOLOD</option>
                                    <option value='PASSI-1'>PASSI-1</option>
                                    <option value='PASSI-2'>PASSI-2</option>
                                    <option value='BALASAN'>BALASAN</option>
                                    <option value='GUIMARAS'>GUIMARAS</option>
                                    <option value='PEMDI BACOLOD'>PEMDI BACOLOD</option>
                                    <option value='EEMSI-GUIMARAS'>EEMSI-GUIMARAS</option>
                                    <option value='AJUY'>AJUY</option>
                                    <option value='MINDORO ROXAS'>MINDORO ROXAS</option>
                                    <option value='3S MINDORO'>3S MINDORO</option>
                                    <option value='MINDORO MANSALAY'>MINDORO MANSALAY</option>
                                    <option value='K-RIDERS ROXAS'>K-RIDERS ROXAS</option>
                                    <option value='IBAJAY'>IBAJAY</option>
                                    <option value='NUMANCIA'>NUMANCIA</option>
                                    <option value='CFCIPRC'>CFCIPRC</option>

                                </select>
                                <?php } else {
        ?>
                                <input type='text' class='form-control' id='branch'
                                    value="<?php echo $_SESSION['user_branch']; ?>" readonly>
                                <input type='hidden' id='branch' value="<?php echo $_SESSION['user_branch']; ?>">
                                <?php }
        ?>
                            </div>
                        </div>

                        <hr>

                        <h5 class='mb-3'>Motorcycle Models</h5>
                        <div id='modelFormsContainer'>
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

<template id='modelFormTemplate'>
  <div class='model-form card mb-3'>
    <div class='card-header d-flex justify-content-between align-items-center'>
      <span class='model-number'>Model #1</span>
      <button type='button' class='btn btn-sm btn-danger remove-model-btn'>
        <i class='bi bi-trash'></i> Remove
      </button>
    </div>
    <div class='card-body'>
      <!-- First Row -->
      <div class='row'>
        <div class='col-md-4 mb-3'>
          <label class='form-label'>Brand</label>
          <select class='form-select model-brand' required>
            <option value=''>Select Brand</option>
            <option value='Suzuki'>Suzuki</option>
            <option value='Honda'>Honda</option>
            <option value='Kawasaki'>Kawasaki</option>
            <option value='Yamaha'>Yamaha</option>
            <option value='Asiastar'>Asiastar</option>
          </select>
        </div>
        <div class='col-md-4 mb-3'>
          <label class='form-label'>Model Name</label>
          <input type='text' class='form-control model-name' required>
        </div>
        <div class='col-md-4 mb-3'>
          <label class='form-label'>Category</label>
          <select class='form-select model-category' required>
            <option value=''>Select Category</option>
            <option value='brandnew'>Brand New</option>
            <option value='repo'>Repo</option>
          </select>
        </div>
      </div>

      <!-- Second Row -->
      <div class='row'>
        <div class='col-md-4 mb-3'>
          <label class='form-label'>Quantity</label>
          <input type='number' class='form-control model-quantity' min='1' value='1' required>
        </div>
        <div class='col-md-4 mb-3'>
          <label class='form-label'>Color</label>
          <input type='text' class='form-control model-color' required>
        </div>
        <div class='col-md-4 mb-3'>
          <label class='form-label'>Inventory Cost</label>
          <input type='number' step='0.01' class='form-control model-inventoryCost'>
        </div>
      </div>

      <!-- Specific Details Section -->
      <div class='specific-details-container mt-3' style='display: none;'>
        <h6 class='fw-semibold mb-3'>Specific Model Details</h6>
        <div class='specific-details-rows'>
        </div>
      </div>
    </div>
  </div>
</template>


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
                                <input type='text' class='form-control' id='editInvoiceNumber' required>
                            </div>
                        </div>
                        <div class='row'>
                            <div class='col-md-4 mb-3'>
                                <label for='editBrand' class='form-label'>Brand</label>
                                <select class='form-select' id='editBrand' required>
                                    <option value='Suzuki'>Suzuki</option>
                                    <option value='Honda'>Honda</option>
                                    <option value='Kawasaki'>Kawasaki</option>
                                    <option value='Yamaha'>Yamaha</option>
                                    <option value='Asiastar'>Asiastar</option>
                                </select>
                            </div>
                            <div class='col-md-4 mb-3'>
                                <label for='editModel' class='form-label'>Model</label>
                                <input type='text' class='form-control' id='editModel' required>
                            </div>
                            <div class='col-md-4 mb-3'>
                                <label for='editCategory' class='form-label'>Category</label>
                                <select class='form-select' id='editCategory' required>
                                    <option value='brandnew'>Brand New</option>
                                    <option value='repo'>Repo</option>
                                </select>
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
                                <label for='editInventoryCost' class='form-label'>Inventory Cost</label>
                                <input type='number' step='0.01' class='form-control' id='editInventoryCost'>
                            </div>
                        </div>
                        <div class='row'>
                            <div class='col-md-6 mb-3'>
                                <label for='editCurrentBranch' class='form-label'>Branch</label>
                                <?php if ( isset( $_SESSION[ 'user_role' ] ) && $_SESSION[ 'user_role' ] === 'admin' ) {
            ?>
                                <!-- Admin can select any branch -->
                                <select class='form-select' id='editCurrentBranch' required>
                                   <option value='HEADOFFICE'>HEADOFFICE</option>
                                    <option value='KINGDOM'>KINGDOM</option>
                                    <option value='TANQUE'>TANQUE</option>
                                    <option value='ROXAS SUZUKI'>ROXAS SUZUKI</option>
                                    <option value='MAMBUSAO'>MAMBUSAO</option>
                                    <option value='SIGMA'>SIGMA</option>
                                    <option value='PRC'>PRC</option>
                                    <option value='BAILAN'>BAILAN</option>
                                    <option value='CUARTERO'>CUARTERO</option>
                                    <option value='JAMINDAN'>JAMINDAN</option>
                                    <option value='ROXAS HONDA'>ROXAS HONDA</option>
                                    <option value='ANTIQUE-1'>ANTIQUE-1</option>
                                    <option value='ANTIQUE-2'>ANTIQUE-2</option>
                                    <option value='DELGADO HONDA'>DELGADO HONDA</option>
                                    <option value='DELGADO SUZUKI'>DELGADO SUZUKI</option>
                                    <option value='JARO-1'>JARO-1</option>
                                    <option value='JARO-2'>JARO-2</option>
                                    <option value='KALIBO MABINI'>KALIBO MABINI</option>
                                    <option value='KALIBO SUZUKI'>KALIBO SUZUKI</option>
                                    <option value='ALTAVAS'>ALTAVAS</option>
                                    <option value='EMAP'>EMAP</option>
                                    <option value='CULASI'>CULASI</option>
                                    <option value='BACOLOD'>BACOLOD</option>
                                    <option value='PASSI-1'>PASSI-1</option>
                                    <option value='PASSI-2'>PASSI-2</option>
                                    <option value='BALASAN'>BALASAN</option>
                                    <option value='GUIMARAS'>GUIMARAS</option>
                                    <option value='PEMDI BACOLOD'>PEMDI BACOLOD</option>
                                    <option value='EEMSI-GUIMARAS'>EEMSI-GUIMARAS</option>
                                    <option value='AJUY'>AJUY</option>
                                    <option value='MINDORO ROXAS'>MINDORO ROXAS</option>
                                    <option value='3S MINDORO'>3S MINDORO</option>
                                    <option value='MINDORO MANSALAY'>MINDORO MANSALAY</option>
                                    <option value='K-RIDERS ROXAS'>K-RIDERS ROXAS</option>
                                    <option value='IBAJAY'>IBAJAY</option>
                                    <option value='NUMANCIA'>NUMANCIA</option>
                                    <option value='CFCIPRC'>CFCIPRC</option>

                                </select>
                                <?php } else {
                ?>
                                <input type='text' class='form-control' id='editCurrentBranch'
                                    value="<?php echo $_SESSION['user_branch']; ?>" readonly>
                                <input type='hidden' id='editCurrentBranchHidden'
                                    value="<?php echo $_SESSION['user_branch']; ?>">
                                <?php }
                ?>
                            </div>
                            <div class='col-md-6 mb-3'>
                                <label for='editStatus' class='form-label'>Status</label>
                                <select class='form-select' id='editStatus' required>
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

  
<div class='modal fade' id='multipleTransferModal' tabindex='-1' aria-labelledby='multipleTransferModalLabel'
        aria-hidden='true'>
    <div class='modal-dialog modal-lg'>
        <div class='modal-content'>
            <div class='modal-header bg-primary text-white'>
                <h5 class='modal-title text-white' id='multipleTransferModalLabel'>
                    <i class='bi bi-truck me-2 text-white'></i>Transfer Multiple Motorcycles
                </h5>
                <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'
                        aria-label='Close'></button>
            </div>
            <div class='modal-body p-0'>
                <form id='multipleTransferForm'>
                    <div class='row g-0'>
                        <div class='col-md-4 border-end bg-light'>
                            <div class='p-4'>
                                <fieldset>
                                    <legend class='fs-6 fw-semibold text-black mb-4'>
                                        <i class='bi bi-geo-alt me-2'></i>Transfer Information
                                    </legend>

                                    <!-- Transfer Invoice Number Input -->
                                    <div class='mb-3'>
                                        <label for='multipleTransferInvoiceNumber' class='form-label small fw-semibold'>
                                            <i class='bi bi-receipt me-1'></i>Transfer Invoice No. <span class='text-danger'>*</span>
                                        </label>
                                        <input type='text' class='form-control form-control-sm'
                                                id='multipleTransferInvoiceNumber' required
                                                placeholder="Enter transfer invoice number">
                                    </div>

                                    <div class='mb-3'>
                                        <label for='multipleFromBranch' class='form-label small fw-semibold'>
                                            <i class='bi bi-geo-alt me-1'></i>From Branch
                                        </label>
                                        <input type='text' class='form-control form-control-sm'
                                                id='multipleFromBranch' readonly>
                                    </div>

                                    <div class='mb-3'>
                                            <label for='multipleToBranch' class='form-label small fw-semibold'>
                                                <i class='bi bi-geo-alt-fill me-1'></i>To Branch <span
                                                    class='text-danger'>*</span>
                                            </label>
                                            <select class='form-select form-select-sm' id='multipleToBranch' required>
                                                <option value=''>Select Destination Branch</option>

                                            </select>
                                        </div>

                                    <div class='mb-3'>
                                        <label for='multipleTransferDate' class='form-label small fw-semibold'>
                                            <i class='bi bi-calendar me-1'></i>Transfer Date <span
                                                    class='text-danger'>*</span>
                                        </label>
                                        <input type='date' class='form-control form-control-sm'
                                                id='multipleTransferDate' required>
                                    </div>

                                    <div class='mb-4'>
                                        <label for='multipleTransferNotes' class='form-label small fw-semibold'>
                                            <i class='bi bi-chat-text me-1'></i>Transfer Notes
                                        </label>
                                        <textarea class='form-control form-control-sm' id='multipleTransferNotes'
                                                rows='3' placeholder='Optional notes about this transfer...'></textarea>
                                    </div>
                                </fieldset>

                                <hr>

                                <fieldset>
                                    <legend class='fs-6 fw-semibold text-black mb-3'>
                                        <i class='bi bi-calculator me-2'></i>Transfer Summary
                                    </legend>

                                    <div class='summary-card p-3 mb-3'
                                            style='background: white; border-radius: 8px; border: 1px solid #e9ecef;'>
                                        <div class='d-flex justify-content-between align-items-center mb-2'>
                                            <span class='small fw-semibold'>Total Units:</span>
                                            <span class='badge bg-primary' id='selectedCount'>0</span>
                                        </div>
                                        <div class='d-flex justify-content-between align-items-center'>
                                            <span class='small fw-semibold'>Total Inventory Cost Value:</span>
                                            <span class='fw-bold text-success'
                                                    id='totalInventoryCostValue'>₱0.00</span>
                                        </div>
                                    </div>

                                    <div class='progress mb-4' style='height: 6px;'>
                                        <div class='progress-bar' id='selectionProgress' style='width: 0%'></div>
                                    </div>

                                    <div class='d-grid'>
                                        <button type='submit' class='btn btn-success btn-sm'>
                                            <i class='bi bi-truck me-2'></i>Transfer Selected Motorcycles
                                        </button>
                                    </div>
                                </fieldset>
                            </div>
                        </div>

                        <div class='col-md-8'>
                            <div class='p-4'>
                                <h6 class='fw-semibold text-primary mb-4'>
                                    <i class='bi bi-search me-2'></i>Motorcycle Selection
                                </h6>
                                <div class='row g-2 mb-3 align-items-end'>
                                    <div class='col-md-8'>
                                        <label class='form-label small fw-semibold'>
                                            <i class='bi bi-upc-scan me-1'></i>Search by Engine Number
                                        </label>
                                        <input type='text' class='form-control form-control-sm' id='engineSearch'
                                                placeholder='Enter engine number...'>
                                    </div>
                                    <div class='col-md-4'>
                                        <div class='d-flex gap-2'>
                                            <button class='btn btn-primary btn-sm w-100 text-white' type='button'
                                                    id='searchEngineBtn'>
                                                <i class='bi bi-search me-1'></i>Search
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class='form-text small text-muted mb-4'>You can search using full or partial
                                    engine numbers.</div>

                                <div class='row g-3'>
                                    <div class='col-md-6'>
                                        <div class='card h-100 shadow-sm'>
                                            <div
                                                    class='card-header py-2 bg-light d-flex justify-content-between align-items-center'>
                                                <span class='fw-semibold small'>
                                                    <i class='bi bi-list-check me-1'></i>Search Results
                                                </span>
                                                <span class='badge bg-secondary' id='searchResultsCount'>0</span>
                                            </div>
                                            <div class='card-body p-0'>
                                                <div class='search-results-container'
                                                        style='max-height: 300px; overflow-y: auto;' id='searchResults'>
                                                    <div class='text-center text-muted py-5'>
                                                        <i class='bi bi-search display-6 mb-2'></i>
                                                        <p class='small'>Search for motorcycles to display results
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class='col-md-6'>
                                        <div class='card h-100 shadow-sm'>
                                            <div
                                                    class='card-header py-2 bg-light d-flex justify-content-between align-items-center'>
                                                <span class='fw-semibold small'>
                                                    <i class='bi bi-check-circle me-1'></i>Selected Items
                                                </span>
                                                <button type='button'
                                                        class='btn btn-outline-danger btn-sm py-0 px-2'
                                                        id='clearSelectionBtn' title='Clear All'>
                                                    <i class='bi bi-trash'></i>
                                                </button>
                                            </div>
                                            <div class='card-body p-0'>
                                                <div class='selected-items-container'
                                                        style='max-height: 300px; overflow-y: auto;'
                                                        id='selectedMotorcyclesList'>
                                                    <div class='text-center text-muted py-5'>
                                                        <i class='bi bi-inbox display-6 mb-2'></i>
                                                        <p class='small'>No motorcycles selected</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

  <div class='modal fade' id='incomingTransfersModal' tabindex='-1' aria-labelledby='incomingTransfersModalLabel'
        aria-hidden='true'>
    <div class='modal-dialog modal-xl'>
        <div class='modal-content'>
            <div class='modal-header bg-primary text-white'>
                <h5 class='modal-title text-white' id='incomingTransfersModalLabel'>Incoming Units Transferred to Your Branch</h5>
                <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body'>
                <div class='row mb-3'>
                    <div class='col-md-6'>
                        <div class='d-flex align-items-center'>
                            <input type='checkbox' id='selectAllTransfers' class='form-check-input me-2'>
                            <label for='selectAllTransfers' class='form-check-label fw-semibold'>Select All</label>
                        </div>
                    </div>
                    <div class='col-md-6 text-end'>
                        <span class='badge bg-info' id='selectedTransfersCount'>0 selected</span>
                    </div>
                </div>
                
                <div class='table-responsive'>
                    <table class='table table-striped table-hover'>
                        <thead class='table-dark'>
                            <tr>
                                <th width='50'>
                                    <input type='checkbox' id='selectAllTransfersHeader' class='form-check-input'>
                                </th>
                                <th>Model</th>
                                <th>Engine No.</th>
                                <th>Frame No.</th>
                                <th>Color</th>
                                <th>Transfer Date</th>
                                <th>From Branch</th>
                                <th>Transfer Invoice</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id='incomingTransfersBody'>
                            <tr>
                                <td colspan='9' class='text-center py-4'>
                                    <div class='spinner-border text-primary' role='status'>
                                        <span class='visually-hidden'>Loading...</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class='alert alert-info mt-3' id='transferSummary' style='display: none;'>
                    <h6 class='alert-heading'>Transfer Summary</h6>
                    <div class='row'>
                        <div class='col-md-4'>
                            <strong>Selected Transfers:</strong> <span id='summarySelectedCount'>0</span>
                        </div>
                        <div class='col-md-4'>
                            <strong>Total Units:</strong> <span id='summaryTotalUnits'>0</span>
                        </div>
                        <div class='col-md-4'>
                            <strong>From Branches:</strong> <span id='summaryFromBranches'>-</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class='modal-footer'>
                <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>
                <button type='button' class='btn btn-warning me-2' id='rejectSelectedBtn' disabled>
                    <i class='bi bi-x-circle me-1'></i>Reject Selected
                </button>
                <button type='button' class='btn btn-success' id='acceptSelectedBtn' disabled>
                    <i class='bi bi-check-circle me-1'></i>Accept Selected
                </button>
            </div>
        </div>
    </div>
</div>

    <div class="modal fade" id="monthlyReportOptionsModal" tabindex="-1"
        aria-labelledby="monthlyReportOptionsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="monthlyReportOptionsModalLabel">Generate Reports</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Month</label>
                        <input type="month" class="form-control" id="reportMonth" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Select Branch</label>
                        <select class="form-select" id="reportBranch">
                            <!-- Options will be populated dynamically -->
                        </select>
                    </div>
                    <div class="mb-3">
    <label class="form-label">Category</label>
    <select class="form-select" id="reportCategoryFilter">
        <option value="all">All</option>
        <option value="brandnew">Brand New</option>
        <option value="repo">Repo</option>
    </select>
</div>

                    <div class="mb-3">
                        <label class="form-label">Select Report Type</label>
                        <select class="form-select" id="reportType" required>
                            <option value="inventory">Monthly Inventory Balance Report</option>
                            <option value="transferred">Monthly Summary of Transferred Stocks</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary text-white" id="generateReportBtn">Generate Report</button>
                </div>
            </div>
        </div>
    </div>

    <div id='monthlyReportPrintContainer' style='display: none;'></div>

    <div class='modal fade' id='monthlyInventoryReportModal' tabindex='-1'
        aria-labelledby='monthlyInventoryReportModalLabel' aria-hidden='true'>
        <div class='modal-dialog modal-xl'>
            <div class='modal-content'>
                <div class='modal-header'>
                    <h5 class='modal-title' id='monthlyInventoryReportModalLabel'>Monthly  Report</h5>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body'>
                    <div class='d-flex justify-content-between mb-3'>
                        <div>
                            <button class='btn btn-sm btn-outline-primary' id='exportMonthlyReportToPDF'>
                                <i class='bi bi-printer'></i> Print Report
                            </button>
                        </div>
                        <div class='text-muted small' id='monthlyReportTimestamp'></div>
                    </div>
                    <div id='monthlyReportContent'>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>
                </div>
            </div>
        </div>
    </div>

      <div class='modal fade' id='sellMotorcycleModal' tabindex='-1' aria-labelledby='sellMotorcycleModalLabel'
        aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header'>
                    <h5 class='modal-title' id='sellMotorcycleModalLabel'>Mark Motorcycle as Sold</h5>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body'>
                    <form id='saleForm'>
                        <input type='hidden' id='sellMotorcycleId'>

                        <div class='mb-3'>
                            <label for='saleDate' class='form-label'>Sale Date <span
                                    class='text-danger'>*</span></label>
                            <input type='date' class='form-control' id='saleDate' required>
                        </div>

                        <div class='mb-3'>
                            <label for='customerName' class='form-label'>Customer Name <span
                                    class='text-danger'>*</span></label>
                            <input type='text' class='form-control' id='customerName' required>
                        </div>

                        <div class='mb-3'>
                            <label for='paymentType' class='form-label'>Payment Type <span
                                    class='text-danger'>*</span></label>
                            <select class='form-select' id='paymentType' onchange='handlePaymentTypeChange()' required>
                                <option value=''>Select Payment Type</option>
                                <option value='COD'>Cash on Delivery ( COD )</option>
                                <option value='Installment'>Installment</option>
                            </select>
                        </div>

                        <div id='codFields' style='display: none;'>
                            <div class='mb-3'>
                                <label for='drNumber' class='form-label'>DR Number <span
                                        class='text-danger'>*</span></label>
                                <input type='text' class='form-control' id='drNumber'>
                            </div>

                            <div class='mb-3'>
                                <label for='codAmount' class='form-label'>COD Amount <span
                                        class='text-danger'>*</span></label>
                                <input type='number' step='0.01' class='form-control' id='codAmount'>
                            </div>
                        </div>

                        <div id='installmentFields' style='display: none;'>
                            <div class='mb-3'>
                                <label for='terms' class='form-label'>Terms ( months ) <span
                                        class='text-danger'>*</span></label>
                                <input type='number' class='form-control' id='terms' min='1'>
                            </div>

                            <div class='mb-3'>
                                <label for='monthlyAmortization' class='form-label'>Monthly Amortization <span
                                        class='text-danger'>*</span></label>
                                <input type='number' step='0.01' class='form-control' id='monthlyAmortization'>
                            </div>
                        </div>
                    </form>
                </div>
                <div class='modal-footer'>
                    <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                    <button type='button' class='btn btn-primary text-white' onclick='submitSale()'>Mark as Sold</button>
                </div>
            </div>
        </div>
    </div>


    <div class='modal fade' id='confirmationModal' tabindex='-1' aria-labelledby='confirmationModalLabel'
        aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header'>
                    <h5 class='modal-title' id='confirmationModalLabel'>Confirm Action</h5>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body'>
                    <p id='confirmationMessage'></p>
                </div>
                <div class='modal-footer'>
                    <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                    <button type='button' class='btn btn-primary text-white' id='confirmActionBtn'>Confirm</button>
                </div>
            </div>
        </div>
    </div>

<div class="modal fade" id="searchTransferReceiptModal" tabindex="-1" aria-labelledby="searchTransferReceiptModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="searchTransferReceiptModalLabel">Search Transfer Receipt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="transferInvoiceSearch" class="form-label">Transfer Invoice Number</label>
                    <input type="text" class="form-control" id="transferInvoiceSearch" placeholder="Enter transfer invoice number">
                </div>
                <div id="searchResultsContainer" class="mt-3" style="display: none;">
                    <h6>Search Results:</h6>
                    <div id="transferSearchResults" class="search-results"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary text-white" id="searchTransferBtn">Search</button>
            </div>
        </div>
    </div>
</div>

  <div class='modal fade' id='transferReceiptModal' tabindex='-1' aria-labelledby='transferReceiptModalLabel' aria-hidden='true'>
    <div class='modal-dialog modal-xl'>
        <div class='modal-content'>
            <div class='modal-header bg-primary text-white'>
                <h5 class='modal-title text-white' id='transferReceiptModalLabel'>
                    <i class='bi bi-receipt me-2'></i>Transfer Receipt
                </h5>
                <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body'>
                <div class='receipt-header mb-4'>
                    <div class='row'>
                        <div class='col-md-6'>
                            <h5 class='mb-1'>SOLID MOTORCYCLE DISTRIBUTORS, INC.</h5>
                            <p class='mb-0 text-muted'>Merchandise Transfer Receipt</p>
                        </div>
                        <div class='col-md-6 text-end'>
                            <p class='mb-0'><strong>Date:</strong> <span id='receiptDate'></span></p>
                            <p class='mb-0'><strong>Transfer Invoice No:</strong> <span id='receiptInvoiceNo'></span></p> 
                            <!-- <p class='mb-0'><strong>Transfer ID:</strong> <span id='receiptTransferId'></span></p> -->
                        </div>
                    </div>
                    <hr>
                    <div class='row'>
                        <div class='col-md-6'>
                            <p class='mb-1'><strong>From:</strong> <span id='receiptFromBranch'></span></p>
                        </div>
                        <div class='col-md-6'>
                            <p class='mb-1'><strong>To:</strong> <span id='receiptToBranch'></span></p>
                        </div>
                    </div>
                </div>

                <div class='table-responsive'>
                    <table class='table table-bordered table-sm'>
                        <thead class='table-light'>
                            <tr>
                                <th>#</th>
                                <th>Brand</th>
                                <th>Model</th>
                                <th>Color</th>
                                <th>Engine Number</th>
                                <th>Frame Number</th>
                                <th class='text-end'>Inventory Cost</th>
                            </tr>
                        </thead>
                        <tbody id='receiptMotorcyclesList'>
                        </tbody>
                        <tfoot class='table-group-divider'>
                            <tr>
                                <td colspan='6' class='text-end fw-bold'>Total Motorcycles:</td>
                                <td class='text-end fw-bold' id='receiptTotalCount'>0</td>
                            </tr>
                            <tr>
                                <td colspan='6' class='text-end fw-bold'>Total Inventory Cost:</td>
                                <td class='text-end fw-bold' id='receiptTotalCost'>₱0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class='mt-4'>
                    <h6>Transfer Notes:</h6>
                    <p id='receiptNotes' class='text-muted fst-italic'>No notes provided.</p>
                </div>
            </div>
            <div class='modal-footer'>
                <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>
                <button type='button' class='btn btn-primary text-white' id='printReceiptBtn'>
                    <i class='bi bi-printer me-2'></i>Print Receipt
                </button>
            </div>
        </div>
    </div>
</div>
    <div class='modal fade' id='successModal' tabindex='-1' aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-body text-center py-4'>
                    <i class='bi bi-check-circle-fill text-success fs-1'></i>
                    <p id='successMessage' class='mt-3'></p>
                </div>
            </div>
        </div>
    </div>

    <div class='modal fade' id='errorModal' tabindex='-1' aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-body text-center py-4'>
                    <i class='bi bi-x-circle-fill text-danger fs-1'></i>
                    <p id='errorMessage' class='mt-3'></p>
                </div>
            </div>
        </div>
    </div>
    <!-- Info Modal -->
<div class="modal fade" id="infoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">Information</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center">
                    <i class="bi bi-info-circle-fill text-info me-3" style="font-size: 2rem;"></i>
                    <span id="infoMessage"></span>
                </div>
            </div>
        </div>
    </div>
</div>


    <div class='modal fade' id='warningModal' tabindex='-1' aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-body text-center py-4'>
                    <i class='bi bi-exclamation-triangle-fill text-warning fs-1'></i>
                    <p id='warningMessage' class='mt-3'></p>
                </div>
            </div>
        </div>
    </div>



    <script src='https://code.jquery.com/jquery-3.6.0.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js'></script>
    <script src='https://unpkg.com/leaflet@1.9.3/dist/leaflet.js'></script>
    <script src='https://printjs-4de6.kxcdn.com/print.min.js'></script>

    <script>
    const currentBranch = '<?php echo $_SESSION['user_branch'] ?? 'RXS-S'; ?>';
    const currentUserBranch = "<?php echo isset($_SESSION['user_branch']) ? $_SESSION['user_branch'] : ''; ?>";
    const currentUserPosition = "<?php echo isset($_SESSION['position']) ? $_SESSION['position'] : ''; ?>";
    const isHeadOffice = currentUserBranch === 'HEADOFFICE';
    const isAdminUser = ['ADMIN', 'IT STAFF', 'HEAD'].includes(currentUserPosition.toUpperCase());
    </script>
    <script src='../js/inventory_management.js'></script>
</body>

</html>