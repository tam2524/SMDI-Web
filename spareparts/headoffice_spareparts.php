<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit();
}
$currentBranch = $_SESSION['user_branch'] ?? 'HEADOFFICE';
$userRole = $_SESSION['user_role'] ?? 'user';
?>
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='utf-8'>
    <title>SMDI - SPARE PARTS INVENTORY</title>
    <meta content='width=device-width, initial-scale=1.0' name='viewport'>
    <link rel='icon' href='../assets/img/smdi_logosmall.png' type='image/png'>
    <link rel='stylesheet' href='https://use.fontawesome.com/releases/v5.15.4/css/all.css' />
    <link href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css' rel='stylesheet'>
    <link href='../css/bootstrap.min.css' rel='stylesheet'>
    <link href='../css/style.css' rel='stylesheet'>
    <style>
        .card-header { font-weight: bold; }
        .table thead th { position: sticky; top: 0; background-color: #f8f9fa; z-index: 10; }
        .modal-body { max-height: 80vh; overflow-y: auto; }
    </style>
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
                <button class='navbar-toggler' type='button' data-bs-toggle='collapse' data-bs-target='#navbarCollapse' aria-controls='navbarCollapse' aria-expanded='false' aria-label='Toggle navigation'>
                    <span class='navbar-toggler-icon'></span>
                </button>
                <div class='collapse navbar-collapse' id='navbarCollapse'>
                    <div class='navbar-nav'>
                        <a href='spareparts_inventory.php' class='nav-item nav-link active'>Home</a>
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
                <h1 class='h5 mb-0'>Spare Parts Inventory Management</h1>
            </div>
            <div class='card-body'>
                <ul class='nav nav-tabs mb-4' id='inventoryTabs' role='tablist'>
                    <li class='nav-item' role='presentation'>
                        <button class='nav-link active' id='dashboard-tab' data-bs-toggle='tab' data-bs-target='#dashboard' type='button' role='tab'>Dashboard</button>
                    </li>
                    <li class='nav-item' role='presentation'>
                        <button class='nav-link' id='management-tab' data-bs-toggle='tab' data-bs-target='#management' type='button' role='tab'>Inventory Management</button>
                    </li>
                    <li class='nav-item' role='presentation'>
                        <button class='nav-link' id='reports-tab' data-bs-toggle='tab' data-bs-target='#reports' type='button' role='tab'>Reports</button>
                    </li>
                </ul>

                <div class='tab-content' id='inventoryTabContent'>
                    <div class='tab-pane fade show active' id='dashboard' role='tabpanel'>
                        <h4>Current Stock Overview</h4>
                        <div class='table-responsive'>
                            <table class='table table-striped' id='partsDashboardTable'>
                                <thead>
                                    <tr>
                                        <th>Part No</th>
                                        <th>Description</th>
                                        <th>Current Stock</th>
                                        <th>Cost</th>
                                        <th>Total Value</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id='partsDashboardBody'>
                                    <tr><td colspan="8" class="text-center py-5"><div class='spinner-border text-primary' role='status'><span class='visually-hidden'>Loading...</span></div></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class='tab-pane fade' id='management' role='tabpanel'>
                        <div class='d-flex justify-content-between mb-4'>
                            <div>
                                <button class='btn btn-primary text-white me-2' data-bs-toggle='modal' data-bs-target='#addPartsInModal'>
                                    <i class='bi bi-plus-circle'></i> Record IN
                                </button>
                                <button class='btn btn-success text-white me-2' data-bs-toggle='modal' data-bs-target='#sellPartsOutModal'>
                                    <i class='bi bi-currency-dollar'></i> Record SALE/OUT
                                </button>
                                <button class='btn btn-warning text-white me-2' data-bs-toggle='modal' data-bs-target='#recordPaymentModal'>
                                    <i class='bi bi-cash'></i> Record Payment
                                </button>
                                <button class='btn btn-info text-white' data-bs-toggle='modal' data-bs-target='#transferPartsModal'>
                                    <i class='bi bi-truck'></i> Record Transfer
                                </button>
                            </div>
                        </div>
                        <div class='alert alert-info' role='alert'>
                            Use the buttons above to manage your spare parts. You can view the overall stock in the Dashboard tab.
                        </div>
                    </div>

                    <div class='tab-pane fade' id='reports' role='tabpanel'>
                        <div class='card mb-3'>
                            <div class='card-header'>Generate Reports</div>
                            <div class='card-body'>
                                <div class='row g-3'>
                                    <div class='col-md-4'>
                                        <label for='reportType' class='form-label'>Report Type</label>
                                        <select id='reportType' class='form-select'>
                                            <option value='aging'>Monthly Aging Report</option>
                                            <option value='sales'>Monthly/Daily Sales Report</option>
                                            <option value='payments'>Monthly/Daily Payments Summary</option>
                                        </select>
                                    </div>
                                    <div class='col-md-4'>
                                        <label for='reportPeriod' class='form-label'>Report Period</label>
                                        <select id='reportPeriod' class='form-select'>
                                            <option value='monthly'>Monthly</option>
                                            <option value='daily'>Daily</option>
                                        </select>
                                    </div>
                                    <div class='col-md-4'>
                                        <label for='reportDate' class='form-label'>Date/Month</label>
                                        <input type='month' id='reportMonth' class='form-control'>
                                        <input type='date' id='reportDay' class='form-control d-none'>
                                    </div>
                                    <div class='col-12'>
                                        <button class='btn btn-primary text-white' id='generateReportBtn'>Generate Report</button>
                                    </div>
                                </div>
                                <div id='reportResults' class='mt-4'></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div class='modal fade' id='addPartsInModal' tabindex='-1' aria-labelledby='addPartsInModalLabel' aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header'>
                    <h5 class='modal-title' id='addPartsInModalLabel'>Record Parts IN</h5>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <form id='addPartsInForm'>
                    <div class='modal-body'>
                        <div class='mb-3'>
                            <label for='in_part_no' class='form-label'>Part No</label>
                            <input type='text' class='form-control' id='in_part_no' name='part_no' required>
                        </div>
                        <div class='mb-3'>
                            <label for='in_description' class='form-label'>Description</label>
                            <input type='text' class='form-control' id='in_description' name='description'>
                        </div>
                        <div class='mb-3'>
                            <label for='in_quantity' class='form-label'>Quantity</label>
                            <input type='number' class='form-control' id='in_quantity' name='quantity' min='1' required>
                        </div>
                        <div class='mb-3'>
                            <label for='in_cost' class='form-label'>Cost</label>
                            <input type='number' step='0.01' class='form-control' id='in_cost' name='cost' min='0' required>
                        </div>
                        <div class='mb-3'>
                            <label for='in_date' class='form-label'>Date</label>
                            <input type='date' class='form-control' id='in_date' name='date' required>
                        </div>
                        <div class='mb-3'>
                            <label for='in_invoice_no' class='form-label'>Invoice / Order Sheet #</label>
                            <input type='text' class='form-control' id='in_invoice_no' name='invoice_no' required>
                        </div>
                        <input type='hidden' id='in_branch' name='branch' value='<?php echo htmlspecialchars($currentBranch); ?>'>
                    </div>
                    <div class='modal-footer'>
                        <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>
                        <button type='submit' class='btn btn-primary text-white'>Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class='modal fade' id='sellPartsOutModal' tabindex='-1' aria-labelledby='sellPartsOutModalLabel' aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header'>
                    <h5 class='modal-title' id='sellPartsOutModalLabel'>Record Parts SALE/OUT</h5>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <form id='sellPartsOutForm'>
                    <div class='modal-body'>
                        <div class='mb-3'>
                            <label for='out_part_no' class='form-label'>Part No</label>
                            <input type='text' class='form-control' id='out_part_no' name='part_no' required>
                        </div>
                        <div class='mb-3'>
                            <label for='out_date' class='form-label'>Date</label>
                            <input type='date' class='form-control' id='out_date' name='date' required>
                        </div>
                        <div class='mb-3'>
                            <label for='out_transaction_type' class='form-label'>Transaction Type</label>
                            <select class='form-select' id='out_transaction_type' name='transaction_type' required>
                                <option value='cash'>Cash</option>
                                <option value='installment'>Installment</option>
                            </select>
                        </div>
                        <div class='mb-3'>
                            <label for='out_quantity' class='form-label'>Quantity</label>
                            <input type='number' class='form-control' id='out_quantity' name='quantity' min='1' required>
                        </div>
                        <div class='mb-3'>
                            <label for='out_amount' class='form-label'>Amount</label>
                            <input type='number' step='0.01' class='form-control' id='out_amount' name='amount' min='0' required>
                        </div>
                        <div class='mb-3'>
                            <label for='out_or_number' class='form-label'>OR Number</label>
                            <input type='text' class='form-control' id='out_or_number' name='or_number' required>
                        </div>
                        <div class='mb-3'>
                            <label for='out_customer_name' class='form-label'>Customer Name</label>
                            <input type='text' class='form-control' id='out_customer_name' name='customer_name' required>
                        </div>
                        <input type='hidden' id='out_branch' name='branch' value='<?php echo htmlspecialchars($currentBranch); ?>'>
                    </div>
                    <div class='modal-footer'>
                        <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>
                        <button type='submit' class='btn btn-success text-white'>Record Sale</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class='modal fade' id='recordPaymentModal' tabindex='-1' aria-labelledby='recordPaymentModalLabel' aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header'>
                    <h5 class='modal-title' id='recordPaymentModalLabel'>Record Payment</h5>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <form id='recordPaymentForm'>
                    <div class='modal-body'>
                        <div class='mb-3'>
                            <label for='payment_date' class='form-label'>Date</label>
                            <input type='date' class='form-control' id='payment_date' name='payment_date' required>
                        </div>
                        <div class='mb-3'>
                            <label for='payment_customer_name' class='form-label'>Customer Name</label>
                            <input type='text' class='form-control' id='payment_customer_name' name='customer_name' required>
                        </div>
                        <div class='mb-3'>
                            <label for='payment_amount' class='form-label'>Amount</label>
                            <input type='number' step='0.01' class='form-control' id='payment_amount' name='amount' min='0' required>
                        </div>
                        <div class='mb-3'>
                            <label for='payment_or_number' class='form-label'>OR Number (from original sale)</label>
                            <input type='text' class='form-control' id='payment_or_number' name='or_number' required>
                        </div>
                        <input type='hidden' id='payment_branch' name='branch' value='<?php echo htmlspecialchars($currentBranch); ?>'>
                    </div>
                    <div class='modal-footer'>
                        <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>
                        <button type='submit' class='btn btn-warning text-white'>Record Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class='modal fade' id='transferPartsModal' tabindex='-1' aria-labelledby='transferPartsModalLabel' aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header'>
                    <h5 class='modal-title' id='transferPartsModalLabel'>Transfer Parts</h5>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <form id='transferPartsForm'>
                    <div class='modal-body'>
                        <div class='mb-3'>
                            <label for='transfer_date' class='form-label'>Transfer Date</label>
                            <input type='date' class='form-control' id='transfer_date' name='transfer_date' required>
                        </div>
                        <div class='mb-3'>
                            <label for='transfer_part_no' class='form-label'>Part No</label>
                            <input type='text' class='form-control' id='transfer_part_no' name='part_no' required>
                        </div>
                        <div class='mb-3'>
                            <label for='transfer_quantity' class='form-label'>Quantity</label>
                            <input type='number' class='form-control' id='transfer_quantity' name='quantity' min='1' required>
                        </div>
                        <div class='mb-3'>
                            <label for='transfer_cost' class='form-label'>Cost</label>
                            <input type='number' step='0.01' class='form-control' id='transfer_cost' name='cost' min='0' required>
                        </div>
                        <div class='mb-3'>
                            <label for='transfer_from_branch' class='form-label'>From Branch</label>
                            <input type='text' class='form-control' id='transfer_from_branch' name='from_branch' value='<?php echo htmlspecialchars($currentBranch); ?>' readonly>
                        </div>
                        <div class='mb-3'>
                            <label for='transfer_to_branch' class='form-label'>To Branch</label>
                            <select class='form-select' id='transfer_to_branch' name='to_branch' required>
                                </select>
                        </div>
                    </div>
                    <div class='modal-footer'>
                        <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>
                        <button type='submit' class='btn btn-info text-white'>Transfer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class='modal fade' id='successModal' tabindex='-1' aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-success text-white'>
                    <h5 class='modal-title text-white'>Success</h5>
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                </div>
                <div class='modal-body'>
                    <p id='successMessage'></p>
                </div>
                <div class='modal-footer'>
                    <button type='button' class='btn btn-success' data-bs-dismiss='modal'>OK</button>
                </div>
            </div>
        </div>
    </div>
    <div class='modal fade' id='errorModal' tabindex='-1' aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-danger text-white'>
                    <h5 class='modal-title'>Error</h5>
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body'>
                    <p id='errorMessage'></p>
                </div>
                <div class='modal-footer'>
                    <button type='button' class='btn btn-danger' data-bs-dismiss='modal'>OK</button>
                </div>
            </div>
        </div>
    </div>

    <script src='https://code.jquery.com/jquery-3.6.0.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js'></script>
    <script>
        const currentBranch = '<?php echo htmlspecialchars($currentBranch); ?>';
        const userRole = '<?php echo htmlspecialchars($userRole); ?>';
    </script>
    <script src='../js/spareparts_inventory.js'></script>
</body>
</html>