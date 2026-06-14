<?php
session_start();
if (!isset($_SESSION['username']) || !in_array($_SESSION['position'], ['Spareparts-Sales', 'Spareparts-Retail'])) {
    header('Location: ../login.html');
    exit();
}
$username = $_SESSION['username'];
$branch = $_SESSION['user_branch'] ?? 'HEADOFFICE';
$today = date('l, F j, Y');

require_once __DIR__ . '/../api/db_config.php';
$stmt = $conn->prepare("SELECT report_header_title FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$dbUser = $stmt->get_result()->fetch_assoc();
$stmt->close();
$reportHeaderTitle = !empty($dbUser['report_header_title']) ? $dbUser['report_header_title'] : 'ROXAS CITY SOLID MERCHANDISING';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDC Payment Management - Spareparts MS</title>
    <link rel="icon" href="../assets/img/smdi_logosmall.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="../css/spareparts_report_print.css?v=<?php echo time(); ?>" rel="stylesheet">
    <style>
        :root {
            --green-900: #003d33;
            --green-800: #004d40;
            --green-700: #00695c;
            --green-600: #00796b;
            --green-400: #26a69a;
            --green-50: #e0f2f1;
            --white: #ffffff;
            --gray-50: #f8fafb;
            --gray-100: #f1f5f4;
            --gray-200: #e2e8e6;
            --gray-500: #6b7280;
            --gray-700: #374151;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, .08), 0 1px 2px rgba(0, 0, 0, .05);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, .08), 0 2px 6px rgba(0, 0, 0, .05);
            --shadow-lg: 0 10px 30px rgba(0, 0, 0, .10), 0 4px 10px rgba(0, 0, 0, .06);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--gray-50);
            color: var(--gray-700);
            min-height: 100vh;
        }

        .navbar-custom {
            background-color: var(--green-800);
            padding: 0.75rem 1rem;
            margin-bottom: 0px;
        }

        .navbar-custom .navbar-brand {
            color: white;
            font-weight: 700;
            display: flex;
            align-items: center;
            font-size: 1.1rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .navbar-custom .back-btn {
            color: white !important;
            font-size: 1.25rem;
            margin-right: 1rem;
            text-decoration: none;
            transition: opacity 0.2s;
        }

        .navbar-custom .back-btn:hover {
            opacity: 0.8;
        }

        .navbar-custom .user-info {
            color: white;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .navbar-custom .user-info i {
            font-size: 1.1rem;
        }

        .navbar-custom .btn-logout {
            border: 1px solid rgba(255, 255, 255, 0.5);
            color: white;
            background: transparent;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            text-decoration: none;
            transition: all 0.2s;
        }

        .navbar-custom .btn-logout:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .main-content {
            max-width: 1300px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .card {
            border: none;
            border-radius: 16px;
            border-top: 5px solid var(--green-700);
            box-shadow: var(--shadow-sm);
            margin-bottom: 2rem;
        }

        .card-header {
            background: #fff;
            padding: 1.2rem 1.5rem;
            border-bottom: 1px solid var(--gray-100);
        }

        .card-title {
            margin: 0;
            font-weight: 700;
            color: var(--green-800);
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-label {
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--gray-700);
        }

        .form-control {
            border-radius: 10px;
            border: 1.5px solid var(--gray-200);
            padding: 0.6rem 1rem;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        .form-control:focus {
            border-color: var(--green-400);
            box-shadow: 0 0 0 4px var(--green-50);
        }

        .btn-primary {
            background: var(--green-800);
            border: none;
            border-radius: 10px;
            padding: 0.7rem 1.5rem;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        .btn-primary:hover {
            background: var(--green-900);
            transform: translateY(-2px);
        }

        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--gray-200);
        }

        .table thead th {
            background: var(--green-800);
            color: #fff;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 1rem;
            border: none;
        }

        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            font-size: 0.88rem;
            border-bottom: 1px solid var(--gray-100);
        }

        .badge-pending {
            background: #fff7ed;
            color: #c2410c;
            border: 1px solid #fdba74;
        }

        .badge-reflected {
            background: #f0fdf4;
            color: #15803d;
            border: 1px solid #86efac;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.75rem;
        }

        .reflect-btn {
            background: var(--green-800);
            color: white;
            border: none;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            transition: all 0.2s;
            cursor: pointer;
        }

        .reflect-btn:hover {
            background: var(--green-900);
            transform: scale(1.05);
        }

        .btn-action {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            border: 1px solid var(--gray-200);
            background: white;
            color: var(--gray-500);
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-action:hover {
            background: var(--green-50);
            color: var(--green-800);
            border-color: var(--green-400);
        }

        .btn-action-delete:hover {
            background: #fef2f2;
            color: #dc2626;
            border-color: #fecaca;
        }

        .autocomplete-results {
            position: absolute;
            width: 100%;
            max-height: 200px;
            overflow-y: auto;
            background: white;
            border: 1px solid #ddd;
            border-radius: 0 0 10px 10px;
            z-index: 1000;
            display: none;
            box-shadow: var(--shadow-md);
        }

        .autocomplete-item {
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
        }

        .autocomplete-item:hover {
            background: var(--green-50);
        }

        .autocomplete-item .bal-text {
            font-size: 0.75rem;
            color: #22c55e;
            font-weight: 700;
            float: right;
        }

        .pagination-container {
            margin-top: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-custom sticky-top no-print">
        <div class="container-fluid px-4">
            <div class="d-flex align-items-center">
                <a href="sales_dashboard.php" class="back-btn">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <span class="navbar-brand">
                    SALES - PDC PAYMENTS
                </span>
            </div>
            <div class="user-info">
                <?php if (isset($_SESSION['username'])): ?>
                    <a href="javascript:void(0)" class="text-white text-decoration-none me-4 position-relative"
                        title="Notifications">
                        <i class="bi bi-bell-fill fs-5"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none"
                            style="font-size: 0.55rem; padding: 0.25em 0.5em;">0</span>
                    </a>
                    <i class="bi bi-person-circle fs-5 opacity-75 d-none d-md-inline"></i>
                    <span class="d-none d-md-inline me-3">
                        <?php echo htmlspecialchars(strtoupper($_SESSION['username'])); ?>
                        <span style="opacity: 0.7; font-size: 0.75rem;">(<?php echo htmlspecialchars($branch); ?>)</span>
                    </span>
                    <a href="../api/logout.php" class="btn-logout">Logout</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="main-content mt-4">
        <!-- Standardized Report Header (Print only) -->
        <div class="report-header-print d-none d-print-block">
            <div class="company-name"><?php echo htmlspecialchars($reportHeaderTitle); ?></div>
            <div class="system-name">Spareparts Management System</div>
            <div class="report-title-container" style="margin-top: 15px;">
                <div class="report-title">PDC PAYMENTS REPORT</div>
                <div class="report-timestamp">Branch: <?php echo htmlspecialchars($branch); ?> | Generated on:
                    <?php echo date('F d, Y h:i A'); ?></div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
            <h4 class="mb-0 fw-bold text-dark"><i class="bi bi-calendar-check me-2 text-success"></i>PDC Payment
                Management</h4>
            <div class="d-flex gap-3 align-items-center">
                <div class="btn-group btn-group-sm bg-white rounded shadow-sm p-1 border no-print">
                    <button type="button" class="btn btn-sm btn-outline-success border-0 px-3 active"
                        onclick="loadPdcs('Pending', this)">Pending</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary border-0 px-3"
                        onclick="loadPdcs('Reflected', this)">Reflected</button>
                </div>
                <button class="btn btn-sm btn-success fw-bold px-3 shadow-sm no-print" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i>Print
                </button>
                <div class="btn-group shadow-sm no-print">
                    <button class='btn btn-sm btn-primary text-white fw-bold px-3' data-bs-toggle='modal'
                        data-bs-target='#encodePdcModal'>
                        <i class='bi bi-plus-circle me-1'></i>Encode PDC
                    </button>
                </div>
                <input type='text' id='tableSearch' class='form-control form-control-sm shadow-sm' style="width: 250px;"
                    placeholder='Search by customer or check #...' autocomplete="off">
            </div>
        </div>

        <div class='table-responsive border-0 shadow-sm bg-white'>
            <table class="table table-hover align-middle mb-0" id="pdcTable">
                <thead class="bg-light border-bottom border-2">
                    <tr>
                        <th class="ps-3">Date</th>
                        <th>Branch</th>
                        <th>Customer Name</th>
                        <th>Bank / Check #</th>
                        <th>Maturity</th>
                        <th class="text-end">Amount</th>
                        <th class="text-center">Status</th>
                        <th class="text-center no-print">Action</th>
                    </tr>
                </thead>
                <tbody id="pdcList">
                    <!-- Dynamic -->
                </tbody>
            </table>
        </div>

        <div class="pagination-container no-print">
            <div class="text-muted small">Showing <span id="pageRange">0-0</span> of <span id="pageTotal">0</span></div>
            <nav id="paginationNav"></nav>
        </div>
    </div>

    <!-- Encode PDC Modal -->
    <div class="modal fade" id="encodePdcModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <form id="pdcForm">
                    <div class="modal-header bg-primary text-white py-3 border-0">
                        <h5 class="modal-title text-white"><i class="bi bi-pencil-square me-2"></i>Encode New PDC</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3 position-relative">
                            <label class="form-label">Payor (Customer)</label>
                            <input type="text" id="customer_search" class="form-control"
                                placeholder="Search customer with balance..." autocomplete="off" required>
                            <input type="hidden" id="selected_customer_name" name="customer_name">
                            <input type="hidden" id="selected_customer_id" name="customer_id">
                            <div id="customer_results" class="autocomplete-results"></div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Bank Name</label>
                                <input type="text" name="bank_name" class="form-control"
                                    placeholder="e.g. RCBC, BDO, Metrobank" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Check No.</label>
                                <input type="text" name="check_no" class="form-control" placeholder="00012345" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Maturity Date</label>
                                <input type="date" name="check_date" class="form-control" required>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="form-label">Amount</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0">₱</span>
                                <input type="number" name="amount" step="0.01"
                                    class="form-control fw-bold border-start-0" placeholder="0.00" required>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="form-label">Remarks (Optional)</label>
                            <textarea name="remarks" class="form-control" rows="2" placeholder="Notes..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0">
                        <button type="button" class="btn btn-light rounded-pill px-4"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4">Save Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit PDC Modal -->
    <div class="modal fade" id="editPdcModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <form id="editPdcForm">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-header bg-warning py-3 border-0">
                        <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit PDC Record</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label">Customer</label>
                            <input type="text" id="edit_customer_display" class="form-control bg-light" readonly>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Bank Name</label>
                                <input type="text" name="bank_name" id="edit_bank_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Check No.</label>
                                <input type="text" name="check_no" id="edit_check_no" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Maturity Date</label>
                                <input type="date" name="check_date" id="edit_check_date" class="form-control" required>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="form-label">Amount</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0">₱</span>
                                <input type="number" name="amount" id="edit_amount" step="0.01"
                                    class="form-control fw-bold border-start-0" required>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="form-label">Remarks (Optional)</label>
                            <textarea name="remarks" id="edit_remarks" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0">
                        <button type="button" class="btn btn-light rounded-pill px-4"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold">Update Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        let currentStatus = 'Pending';
        let currentPage = 1;
        let searchQuery = '';

        $(document).ready(function () {
            loadPdcs('Pending');

            $('#tableSearch').on('input', function () {
                searchQuery = $(this).val();
                currentPage = 1;
                loadPdcs(currentStatus);
            });

            // Customer Autocomplete
            let timeout = null;
            $('#customer_search').on('input', function () {
                clearTimeout(timeout);
                const term = $(this).val();
                if (term.length < 2) {
                    $('#customer_results').hide();
                    return;
                }

                timeout = setTimeout(() => {
                    $.get('../api/pdc_api.php', { action: 'get_customers', term: term }, function (response) {
                        if (response.success) {
                            let html = '';
                            response.customers.forEach(cust => {
                                html += `<div class="autocomplete-item" data-id="${cust.id}" data-name="${cust.customer_name}">
                                            <span class="bal-text">₱${parseFloat(cust.total_balance).toLocaleString()}</span>
                                            <strong>${cust.customer_name}</strong>
                                         </div>`;
                            });
                            $('#customer_results').html(html).show();
                        }
                    });
                }, 300);
            });

            $(document).on('click', '.autocomplete-item', function () {
                const name = $(this).data('name');
                const id = $(this).data('id');
                $('#customer_search').val(name);
                $('#selected_customer_name').val(name);
                $('#selected_customer_id').val(id);
                $('#customer_results').hide();
            });

            // Save PDC
            $('#pdcForm').on('submit', function (e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'save_pdc');

                $.ajax({
                    url: '../api/pdc_api.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        if (response.success) {
                            Swal.fire('Success', response.message, 'success');
                            $('#pdcForm')[0].reset();
                            $('#encodePdcModal').modal('hide');
                            loadPdcs('Pending');
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    }
                });
            });

            // Update PDC
            $('#editPdcForm').on('submit', function (e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'update_pdc');

                $.ajax({
                    url: '../api/pdc_api.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        if (response.success) {
                            Swal.fire('Updated', response.message, 'success');
                            $('#editPdcModal').modal('hide');
                            loadPdcs(currentStatus);
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    }
                });
            });
        });

        function loadPdcs(status, btn) {
            currentStatus = status;
            if (btn) {
                $(btn).siblings().removeClass('active border-0').addClass('border-0');
                $(btn).addClass('active').removeClass('border-0');
            }
            $.get('../api/pdc_api.php', {
                action: 'list_pdc',
                status: status,
                search: searchQuery,
                page: currentPage
            }, function (response) {
                if (response.success) {
                    let html = '';
                    if (response.pdcs.length === 0) {
                        html = '<tr><td colspan="7" class="text-center text-muted py-5">No records found.</td></tr>';
                    } else {
                        response.pdcs.forEach(pdc => {
                            const badgeClass = pdc.status === 'Pending' ? 'badge-pending' : 'badge-reflected';
                            const actionHtml = pdc.status === 'Pending'
                                ? `<button class="reflect-btn me-2" onclick="reflectPdc(${pdc.id})"><i class="bi bi-check2-circle"></i> Reflect</button>
                                   <div class="d-inline-flex gap-1 no-print">
                                       <a href="javascript:void(0)" class="btn-action" onclick="editPdc(${JSON.stringify(pdc).replace(/"/g, '&quot;')})"><i class="bi bi-pencil"></i></a>
                                       <a href="javascript:void(0)" class="btn-action btn-action-delete" onclick="deletePdc(${pdc.id})"><i class="bi bi-trash"></i></a>
                                   </div>`
                                : `<span class="text-muted small">${pdc.reflected_date}</span>`;

                            html += `
                                <tr>
                                    <td class="ps-3">${pdc.created_at ? pdc.created_at.split(' ')[0] : '-'}</td>
                                    <td>${pdc.branch || 'HEADOFFICE'}</td>
                                    <td><strong>${pdc.customer_name || 'N/A'}</strong></td>
                                    <td>${pdc.bank_name} <br> <small class="text-muted">#${pdc.check_no}</small></td>
                                    <td>${pdc.check_date}</td>
                                    <td class="text-end fw-bold text-primary">₱${parseFloat(pdc.amount).toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
                                    <td class="text-center"><span class="badge ${badgeClass}">${pdc.status}</span></td>
                                    <td class="text-center no-print">${actionHtml}</td>
                                </tr>
                            `;
                        });
                    }
                    $('#pdcList').html(html);
                    $('#totalRecords').text(response.total + ' Records');

                    // Update Pagination
                    updatePagination(response);
                }
            });
        }

        function updatePagination(data) {
            const rangeStart = data.pdcs.length > 0 ? (data.page - 1) * data.limit + 1 : 0;
            const rangeEnd = (data.page - 1) * data.limit + data.pdcs.length;
            $('#pageRange').text(`${rangeStart}-${rangeEnd}`);
            $('#pageTotal').text(data.total);

            let html = '<ul class="pagination pagination-sm mb-0">';
            html += `<li class="page-item ${data.page === 1 ? 'disabled' : ''}"><a class="page-link" href="javascript:void(0)" onclick="changePage(${data.page - 1})">Previous</a></li>`;

            for (let i = 1; i <= data.total_pages; i++) {
                if (i === 1 || i === data.total_pages || (i >= data.page - 1 && i <= data.page + 1)) {
                    html += `<li class="page-item ${i === data.page ? 'active' : ''}"><a class="page-link" href="javascript:void(0)" onclick="changePage(${i})">${i}</a></li>`;
                } else if (i === data.page - 2 || i === data.page + 2) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
            }

            html += `<li class="page-item ${data.page === data.total_pages ? 'disabled' : ''}"><a class="page-link" href="javascript:void(0)" onclick="changePage(${data.page + 1})">Next</a></li>`;
            html += '</ul>';
            $('#paginationNav').html(html);
        }

        function changePage(page) {
            currentPage = page;
            loadPdcs(currentStatus);
        }

        function editPdc(pdc) {
            $('#edit_id').val(pdc.id);
            $('#edit_customer_display').val(pdc.customer_name);
            $('#edit_bank_name').val(pdc.bank_name);
            $('#edit_check_no').val(pdc.check_no);
            $('#edit_check_date').val(pdc.check_date);
            $('#edit_amount').val(pdc.amount);
            $('#edit_remarks').val(pdc.remarks);
            $('#editPdcModal').modal('show');
        }

        function deletePdc(id) {
            Swal.fire({
                title: 'Delete PDC?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('../api/pdc_api.php', { action: 'delete_pdc', id: id }, function (response) {
                        if (response.success) {
                            Swal.fire('Deleted!', response.message, 'success');
                            loadPdcs(currentStatus);
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    });
                }
            });
        }

        function reflectPdc(id) {
            Swal.fire({
                title: 'Reflect Payment?',
                text: "This will deduct the check amount from the customer's balance. Continue?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#004d40',
                confirmButtonText: 'Yes, Reflect'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('../api/pdc_api.php', { action: 'reflect_pdc', id: id }, function (response) {
                        if (response.success) {
                            Swal.fire('Reflected!', response.message, 'success');
                            loadPdcs('Pending');
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    });
                }
            });
        }
    </script>
</body>

</html>