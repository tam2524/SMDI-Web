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
    <title>Employee Records - Spareparts MS</title>
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
            <h2 class="fw-bold text-dark mb-0"><i class="bi bi-person-vcard me-2"></i>Sales Force Records</h2>
            <a href="<?php echo $backLink; ?>" class="btn btn-outline-secondary btn-sm">Back</a>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Employee Name</th>
                                <th>Role</th>
                                <th>Branch</th>
                                <th>Contact Number</th>
                                <th class="text-center pe-4">Created At</th>
                            </tr>
                        </thead>
                        <tbody id="employeeBody">
                            <tr><td colspan="5" class="text-center py-5">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        $(document).ready(function() {
            $.get('../api/spareparts_inventory.php', { action: 'get_sales_force' }, function(res) {
                if (res.success && res.data) {
                    let html = '';
                    res.data.forEach(e => {
                        html += `
                            <tr>
                                <td class="ps-4 fw-bold text-primary">${e.name}</td>
                                <td><span class="badge bg-light text-success border border-success">${e.role || 'Sales Force'}</span></td>
                                <td>${e.branch || '—'}</td>
                                <td>${e.contact_number || '—'}</td>
                                <td class="text-center pe-4 text-muted small">${e.created_at || '—'}</td>
                            </tr>
                        `;
                    });
                    $('#employeeBody').html(html);
                } else {
                    $('#employeeBody').html('<tr><td colspan="5" class="text-center py-5 text-muted">No records found.</td></tr>');
                }
            });
        });
    </script>
</body>
</html>
