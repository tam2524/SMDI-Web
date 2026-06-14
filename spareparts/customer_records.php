<?php
session_start();
$adminRoles = ['Admin', 'Head', 'itsuperadmin', 'Admin Spareparts', 'Spareparts-Admin', 'Spareparts-Owner'];
$allowedRoles = array_merge($adminRoles, ['Spareparts-Sales', 'Spareparts-Retail']);
if (!isset($_SESSION['username']) || !in_array($_SESSION['position'], $allowedRoles)) {
    header('Location: ../login.html');
    exit();
}
$username = $_SESSION['username'];
$userRole = $_SESSION['position'] ?? 'user';
$branch = $_SESSION['user_branch'] ?? 'HEADOFFICE';

$userRoleLower = strtolower(trim($userRole));
$backLink = 'sales_dashboard.php';
if ($userRoleLower === 'spareparts-owner') {
    $backLink = 'owner_dashboard.php';
} elseif (in_array($userRoleLower, ['admin', 'head', 'itsuperadmin', 'admin spareparts', 'spareparts-admin'])) {
    $backLink = 'admin_dashboard.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Records - Spareparts MS</title>
    <link rel="icon" href="../assets/img/smdi_logosmall.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f9fafb; padding: 2rem; }
        .card { border-radius: 12px; border: none; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .table thead th { background: #f3f4f6; color: #374151; font-weight: 600; font-size: 0.75rem; text-transform: uppercase; }
        .btn-primary { background: #004d40; border: none; }
        .btn-primary:hover { background: #003d33; }
    </style>
</head>
<body>

    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-dark mb-0"><i class="bi bi-people me-2"></i>Customer Records</h2>
            <div class="d-flex gap-2">
                <input type="text" id="customerSearch" class="form-control" style="width: 280px;" placeholder="Search by name, address, contact, branch...">
                <a href="<?php echo $backLink; ?>" class="btn btn-outline-secondary">Back</a>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Name</th>
                                <th>Address</th>
                                <th>Contact No</th>
                                <th>Branch</th>
                                <th>Created At</th>
                                <th class="text-center pe-4">Total Purchases</th>
                            </tr>
                        </thead>
                        <tbody id="customerBody">
                            <tr><td colspan="6" class="text-center py-5">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        $(document).ready(function() {
            let customersData = [];

            function renderCustomers(data) {
                if (data && data.length) {
                    let html = '';
                    data.forEach(c => {
                        html += `
                            <tr>
                                <td class="ps-4 fw-bold text-primary">${c.name}</td>
                                <td>${c.address || '—'}</td>
                                <td>${c.contact_no || '—'}</td>
                                <td>${c.branch || '—'}</td>
                                <td>${c.created_at || '—'}</td>
                                <td class="text-center pe-4"><span class="badge bg-secondary">${c.purchase_count || 0}</span></td>
                            </tr>
                        `;
                    });
                    $('#customerBody').html(html);
                } else {
                    $('#customerBody').html('<tr><td colspan="6" class="text-center py-5 text-muted">No records found.</td></tr>');
                }
            }

            $.get('../api/spareparts_inventory.php', { action: 'searchUniqueCustomers' }, function(res) {
                if (typeof res === 'string') {
                    try { res = JSON.parse(res); } catch(e) {}
                }
                customersData = res || [];
                renderCustomers(customersData);
            }, 'json');

            $('#customerSearch').on('input', function() {
                const q = $(this).val().toLowerCase().trim();
                if (!q) {
                    renderCustomers(customersData);
                    return;
                }
                const filtered = customersData.filter(c => 
                    (c.name || '').toLowerCase().includes(q) ||
                    (c.address || '').toLowerCase().includes(q) ||
                    (c.contact_no || '').toLowerCase().includes(q) ||
                    (c.branch || '').toLowerCase().includes(q)
                );
                renderCustomers(filtered);
            });
        });
    </script>
</body>
</html>
