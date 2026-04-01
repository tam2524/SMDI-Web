<?php
session_start();
if (!isset($_SESSION['username']) || !in_array($_SESSION['position'], ['Spareparts-Warehouse', 'Spareparts-Admin', 'Spareparts-Owner'])) {
    header('Location: ../login.html');
    exit();
}
$username = $_SESSION['username'];
$branch = $_SESSION['user_branch'] ?? 'HEADOFFICE';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payable Report - Spareparts MS</title>
    <link rel="icon" href="../assets/img/smdi_logosmall.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="../css/spareparts_premium.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link href="../css/spareparts_report_print.css?v=<?php echo time(); ?>" rel="stylesheet">
    <style>
        :root {
            --primary: #c2410c;
            --primary-bg: #fff7ed;
            --secondary: #6b7280;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f9fafb;
            padding: 2rem;
        }

        .page-header {
            margin-bottom: 2rem;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 1rem;
        }

        .card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .table thead th {
            background: #f3f4f6;
            color: #374151;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
        }

        .badge-active {
            background: #fee2e2;
            color: #b91c1c;
        }

        .badge-paid {
            background: #dcfce7;
            color: #15803d;
        }
    </style>
</head>

<body>

    <div class="container-fluid">
        <div class="report-header-print d-none d-print-block">
            <div class="company-name">ROXAS CITY SOLID MERCHANDISING</div>
            <div class="company-address">Pueblo de Panay, Lawaan, Roxas City, Capiz</div>
            <div class="system-name">Spareparts Management System</div>
            <div class="report-title-container" style="margin-top: 15px;">
                <div class="report-title">ACCOUNTS PAYABLE (SUPPLIER AGING)</div>
                <div class="report-timestamp">Branch: <?php echo htmlspecialchars($branch); ?> | Generated on: <?php echo date('F d, Y h:i A'); ?></div>
            </div>
        </div>

        <div class="page-header d-flex justify-content-between align-items-center no-print">
            <div>
                <h2 class="fw-bold text-dark mb-0"><i class="bi bi-file-earmark-medical me-2"></i>Payable Report</h2>
                <p class="text-muted mb-0">Summary of outstanding balances to suppliers
                    (<?php echo htmlspecialchars($branch); ?>)</p>
            </div>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-primary btn-sm px-4"><i
                        class="bi bi-printer me-2"></i>Print Report</button>
                <a href="warehouse_dashboard.php" class="btn btn-outline-secondary btn-sm"><i
                        class="bi bi-arrow-left"></i> Back to Dashboard</a>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Supplier Name</th>
                                        <th>Invoice / DR #</th>
                                        <th>Date Received</th>
                                        <th class="text-end">Total Amount</th>
                                        <th class="text-end">Balance</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center pe-4">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="payablesBody">
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <div class="spinner-border text-primary"></div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Settlement Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="paymentForm">
                        <input type="hidden" id="pay_id">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Supplier</label>
                            <input type="text" id="pay_supplier" class="form-control-plaintext fw-bold" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Outstanding Balance</label>
                            <input type="text" id="pay_balance" class="form-control-plaintext text-danger fw-bold fs-5"
                                readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Amount to Pay</label>
                            <input type="number" id="pay_amount" class="form-control form-control-lg" step="0.01"
                                required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Reference (Check # / Ref #)</label>
                            <input type="text" id="pay_ref" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Confirm Payment</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function () {
            loadPayables();

            $('#paymentForm').on('submit', function (e) {
                e.preventDefault();
                const id = $('#pay_id').val();
                const amount = $('#pay_amount').val();
                const ref = $('#pay_ref').val();

                $.post('../api/payable_api.php', { action: 'record_payment', id: id, amount: amount, reference: ref }, function (res) {
                    if (res.success) {
                        Swal.fire('Success', 'Payment recorded successfully.', 'success');
                        bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
                        loadPayables();
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                });
            });
        });

        function loadPayables() {
            $.get('../api/payable_api.php', { action: 'list' }, function (res) {
                if (res.success) {
                    let html = '';
                    if (res.data.length === 0) {
                        html = '<tr><td colspan="7" class="text-center py-5 text-muted">No payable records found.</td></tr>';
                    } else {
                        res.data.forEach(row => {
                            const statusBadge = row.status === 'Active' ? 'badge-active' : 'badge-paid';
                            const actionBtn = row.status === 'Active'
                                ? `<button class="btn btn-sm btn-primary" onclick="openPaymentModal(${row.id}, '${row.supplier_name}', ${row.balance})">Pay</button>`
                                : `<span class="text-muted small">Paid</span>`;

                            html += `
                                <tr>
                                    <td class="ps-4 fw-bold">${row.supplier_name}</td>
                                    <td>${row.invoice_no}</td>
                                    <td>${row.date_received}</td>
                                    <td class="text-end">₱${parseFloat(row.total_amount).toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
                                    <td class="text-end fw-bold text-danger">₱${parseFloat(row.balance).toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
                                    <td class="text-center"><span class="badge ${statusBadge}">${row.status}</span></td>
                                    <td class="text-center pe-4">${actionBtn}</td>
                                </tr>
                            `;
                        });
                    }
                    $('#payablesBody').html(html);
                }
            });
        }

        function openPaymentModal(id, supplier, balance) {
            $('#pay_id').val(id);
            $('#pay_supplier').val(supplier);
            $('#pay_balance').val('₱' + parseFloat(balance).toLocaleString());
            $('#pay_amount').val(balance);
            $('#pay_ref').val('');
            new bootstrap.Modal(document.getElementById('paymentModal')).show();
        }
    </script>
</body>

</html>