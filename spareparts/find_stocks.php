<?php
session_start();
if (!isset($_SESSION['username']) || !in_array($_SESSION['position'], ['Spareparts-Warehouse', 'Spareparts-Sales', 'Spareparts-Owner', 'Spareparts-Admin', 'Owner', 'Admin'])) {
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
    <title>Find Stocks - Spareparts MS</title>
    <link rel="icon" href="../assets/img/smdi_logosmall.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../css/spareparts_premium.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --smdi-green: #004d40;
            --smdi-green-dark: #00251a;
            --smdi-green-light: #26a69a;
            --smdi-green-bg: #e0f2f1;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            color: #333;
            font-size: 0.9rem;
        }

        .navbar { 
            background-color: var(--smdi-green) !important;
            padding: 0.75rem 1rem;
        }
        
        .navbar-brand {
            font-size: 1.1rem;
            letter-spacing: 0.5px;
        }

        .back-btn {
            color: white !important;
            font-size: 1.25rem;
            margin-right: 1rem;
            text-decoration: none;
            transition: opacity 0.2s;
        }

        .back-btn:hover {
            opacity: 0.8;
        }

        .user-profile {
            display: flex;
            align-items: center;
            color: white;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
        }

        .user-profile i {
            font-size: 1.1rem;
            margin-right: 0.5rem;
        }
        
        .text-primary { color: var(--smdi-green) !important; }
        .bg-primary { background-color: var(--smdi-green) !important; }
        .btn-primary { background-color: var(--smdi-green) !important; border-color: var(--smdi-green) !important; }
        
        .search-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            background: #fff;
        }

        .search-input {
            border-radius: 50px;
            padding: 12px 25px;
            border: 2px solid #eee;
            transition: all 0.3s;
        }

        .search-input:focus {
            border-color: var(--smdi-green-light);
            box-shadow: 0 0 10px rgba(38, 166, 154, 0.2);
        }

        .result-table thead th {
            background-color: var(--smdi-green-bg);
            color: var(--smdi-green);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            padding: 15px;
            border: none;
        }

        .result-table tbody td {
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f0f0;
        }

        .stock-badge {
            font-weight: 700;
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top shadow-sm">
        <div class="container-fluid">
            <div class="d-flex align-items-center">
                <a href="<?php 
                    $role = $_SESSION['position'] ?? '';
                    if ($role === 'Spareparts-Sales' || $role === 'Spareparts-Retail') {
                        echo 'sales_dashboard.php';
                    } elseif ($role === 'Spareparts-Owner') {
                        echo 'owner_dashboard.php';
                    } elseif ($role === 'Spareparts-Admin') {
                        echo 'admin_dashboard.php';
                    } else {
                        echo 'warehouse_dashboard.php';
                    }
                ?>" class="back-btn">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <span class="navbar-brand fw-bold mb-0 text-uppercase">Find Stocks</span>
            </div>
            <div class="ms-auto d-flex align-items-center">
                <div class="user-profile me-3">
                    <i class="bi bi-person-circle"></i>
                    <span><?php echo htmlspecialchars($branch); ?></span>
                </div>
                <a class="btn btn-outline-light btn-sm rounded-pill px-3" href="../api/logout.php" style="font-size: 0.75rem;">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container" style="margin-top: 100px; max-width: 1100px;">
        <div class="search-card p-4 mb-4">
            <div class="text-center mb-4">
                <h3 class="fw-bold text-dark-green">Global Stock Search</h3>
                <p class="text-muted">Search for parts across all branches and locations</p>
            </div>
            <div class="input-group">
                <input type="text" id="globalSearchInput" class="form-control search-input" placeholder="Type Part No, Description, or Brand..." autofocus>
                <button class="btn btn-primary px-4 ms-2 rounded-pill fw-bold" id="globalSearchBtn">
                    <i class="bi bi-search me-2"></i> SEARCH
                </button>
            </div>
        </div>

        <div id="partList">
            <div class="text-center py-5">
                <div class="mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 rounded-circle" style="width: 100px; height: 100px;">
                        <i class="bi bi-search text-white" style="font-size: 3rem;"></i>
                    </div>
                </div>
                <h5 class="fw-bold">Ready to search?</h5>
                <p class="text-muted">Use the search box above or pick a Quick Filter to browse inventory.</p>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/spareparts_stock_card.js"></script>
</body>
</html>
