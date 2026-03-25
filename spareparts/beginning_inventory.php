<?php
require_once '../api/db_config.php';

// Check if user is logged in and has appropriate role
$allowedRoles = ['Spareparts-Admin', 'Spareparts-Owner', 'Spareparts-Warehouse', 'Spareparts-Sales'];
if (!isset($_SESSION['username']) || !in_array($_SESSION['position'], $allowedRoles)) {
    header('Location: ../login.html');
    exit();
}

// Ensure module settings table exists
$conn->query("CREATE TABLE IF NOT EXISTS spareparts_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");
$conn->query("INSERT IGNORE INTO spareparts_settings (setting_key, setting_value) VALUES ('beginning_inventory_enabled', 'true')");

// Check if module is enabled
$checkSetting = $conn->query("SELECT setting_value FROM spareparts_settings WHERE setting_key = 'beginning_inventory_enabled'");
$moduleEnabled = true;
if ($checkSetting && $row = $checkSetting->fetch_assoc()) {
    $moduleEnabled = ($row['setting_value'] === 'true');
}

if (!$moduleEnabled) {
    echo "<div style='text-align:center; padding:50px;'><h1>Access Denied</h1><p>The Beginning Inventory module has been disabled by the administrator.</p><a href='javascript:history.back()'>Go Back</a></div>";
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
    <title>Beginning Inventory Entry - Spareparts MS</title>
    <link rel="icon" href="../assets/img/smdi_logosmall.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        :root {
            --green-900: #003d33;
            --green-800: #004d40;
            --green-700: #00695c;
            --green-600: #00796b;
            --green-400: #26a69a;
            --green-50:  #e0f2f1;
            --white: #ffffff;
            --gray-50: #f8fafb;
            --gray-100: #f1f5f4;
            --gray-200: #e2e8e6;
            --gray-500: #6b7280;
            --gray-700: #374151;
            --shadow-sm: 0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.05);
            --shadow-md: 0 4px 12px rgba(0,0,0,.08), 0 2px 6px rgba(0,0,0,.05);
            --shadow-lg: 0 10px 30px rgba(0,0,0,.10), 0 4px 10px rgba(0,0,0,.06);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--gray-50);
            color: var(--gray-700);
            min-height: 100vh;
        }

        .top-nav {
            background: var(--green-800);
            padding: 0 2rem;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky; top: 0; z-index: 100;
            box-shadow: 0 2px 12px rgba(0,0,0,.25);
        }
        .nav-brand {
            display: flex; align-items: center; gap: 12px;
            text-decoration: none;
        }
        .nav-brand img { height: 34px; border-radius: 6px; }
        .nav-brand-text {
            font-size: 1rem; font-weight: 700; color: var(--white);
            letter-spacing: 0.3px; text-transform: uppercase;
        }

        .hero-banner {
            background: linear-gradient(135deg, var(--green-900) 0%, var(--green-700) 60%, var(--green-400) 100%);
            padding: 2.5rem 2rem 3.5rem;
            position: relative; overflow: hidden;
        }
        .hero-banner::before {
            content: ''; position: absolute; top: -60px; right: -60px;
            width: 300px; height: 300px;
            background: rgba(255,255,255,0.04); border-radius: 50%;
        }
        .hero-content { position: relative; z-index: 1; }
        .hero-greeting {
            font-size: 1.65rem; font-weight: 800; color: var(--white);
            margin: 0 0 4px; letter-spacing: -0.3px;
        }
        .hero-sub {
            color: rgba(255,255,255,0.72); font-size: 0.875rem; font-weight: 400;
            display: flex; align-items: center; gap: 8px;
        }

        .main-content {
            max-width: 1300px; margin: -1.5rem auto 3rem;
            padding: 0 2rem; position: relative; z-index: 2;
        }

        .excel-table-container {
            background: var(--white);
            border-radius: 14px;
            border: 1.5px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            padding: 2.5rem;
        }

        .excel-table {
            width: 100%;
            border-collapse: collapse;
        }

        .excel-table th {
            background: var(--green-800);
            color: white;
            padding: 12px 15px;
            text-align: left;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .excel-table td {
            padding: 0;
            border: 1px solid var(--gray-200);
        }

        .excel-input {
            width: 100%;
            height: 100%;
            border: none;
            padding: 10px 15px;
            font-size: 0.9rem;
            outline: none;
            transition: all 0.2s;
        }

        .excel-input:focus {
            background: var(--green-50);
            box-shadow: inset 0 0 0 2px var(--green-400);
        }

        .btn-premium {
            background: var(--green-800);
            color: white;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-premium:hover {
            background: var(--green-900);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .btn-outline-premium {
            background: transparent;
            color: var(--green-800);
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            border: 2px solid var(--green-800);
        }

        .btn-outline-premium:hover {
            background: var(--green-50);
            color: var(--green-900);
        }

        .row-actions {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
        }

        .btn-remove-row {
            color: #d32f2f;
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .btn-remove-row:hover {
            transform: scale(1.2);
        }

        .sticky-footer {
            background: var(--white);
            padding: 1.5rem 2rem;
            border-top: 1.5px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            bottom: 0;
            z-index: 10;
        }
    </style>
</head>
<body>

    <nav class="top-nav">
        <a class="nav-brand" href="javascript:history.back()">
            <img src="../assets/img/rcsm_logo.jpg" alt="SMDI Logo">
            <span class="nav-brand-text">Beginning Inventory Entry</span>
        </a>
        <div class="nav-right">
            <button onclick="history.back()" class="btn btn-outline-light btn-sm rounded-pill"><i class="bi bi-arrow-left"></i> Back</button>
        </div>
    </nav>

    <div class="hero-banner">
        <div class="hero-content">
            <h1 class="hero-greeting">Beginning Inventory</h1>
            <p class="hero-sub"><i class="bi bi-geo-alt"></i> Branch: <?php echo htmlspecialchars($branch); ?></p>
        </div>
    </div>

    <div class="main-content">
        <div class="excel-table-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="fw-bold mb-1">Stock Entry Table</h5>
                    <p class="text-muted small">Enter your beginning stock levels here. All fields are required for each row.</p>
                </div>
                <div class="d-flex gap-2">
                    <button id="addRowBtn" class="btn btn-outline-premium"><i class="bi bi-plus-circle"></i> Add Row</button>
                    <button id="importExcelBtn" class="btn btn-outline-premium"><i class="bi bi-file-earmark-excel"></i> Bulk Import</button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="excel-table" id="inventoryTable">
                    <thead>
                        <tr>
                            <th style="width: 200px;">Part Number</th>
                            <th>Brand Name</th>
                            <th>Part Name / Description</th>
                            <th style="width: 120px;">Qty</th>
                            <th style="width: 150px;">Cost (₱)</th>
                            <th style="width: 60px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="inventoryBody">
                        <!-- Initial rows -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="sticky-footer shadow-lg">
        <div>
            <span class="text-muted fw-bold">Total Items: <span id="rowCountDisplay">0</span></span>
        </div>
        <div class="d-flex gap-3">
            <button id="clearAllBtn" class="btn btn-outline-danger px-4">Clear All</button>
            <button id="saveInventoryBtn" class="btn btn-premium px-5"><i class="bi bi-save"></i> Save Beginning Inventory</button>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            let rowCount = 0;

            function addRow(data = {}) {
                rowCount++;
                const row = `
                    <tr id="row-${rowCount}">
                        <td><input type="text" class="excel-input part-no" placeholder="Part Number" value="${data.part_no || ''}"></td>
                        <td><input type="text" class="excel-input brand" placeholder="Brand Name" value="${data.brand || ''}"></td>
                        <td><input type="text" class="excel-input description" placeholder="Description" value="${data.description || ''}"></td>
                        <td><input type="number" class="excel-input qty" placeholder="0" min="1" value="${data.qty || ''}"></td>
                        <td><input type="number" class="excel-input cost" placeholder="0.00" step="0.01" value="${data.cost || ''}"></td>
                        <td class="text-center">
                            <button class="btn-remove-row" onclick="removeRow(${rowCount})"><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>
                `;
                $('#inventoryBody').append(row);
                updateCount();
            }

            window.removeRow = function(id) {
                if ($('#inventoryBody tr').length > 1) {
                    $(`#row-${id}`).remove();
                    updateCount();
                } else {
                    Swal.fire('Error', 'At least one row is required.', 'error');
                }
            }

            function updateCount() {
                $('#rowCountDisplay').text($('#inventoryBody tr').length);
            }

            // Initial 5 rows
            for (let i = 0; i < 5; i++) addRow();

            $('#addRowBtn').click(function() {
                addRow();
            });

            $('#clearAllBtn').click(function() {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "This will clear all rows from the table.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#004d40',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, clear all!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#inventoryBody').empty();
                        addRow();
                    }
                });
            });

            $('#saveInventoryBtn').click(function() {
                const items = [];
                let hasEmpty = false;

                $('#inventoryBody tr').each(function() {
                    const row = $(this);
                    const item = {
                        part_no: row.find('.part-no').val().trim(),
                        brand: row.find('.brand').val().trim(),
                        description: row.find('.description').val().trim(),
                        qty: row.find('.qty').val().trim(),
                        cost: row.find('.cost').val().trim()
                    };

                    if (!item.part_no || !item.brand || !item.description || !item.qty || !item.cost) {
                        hasEmpty = true;
                    }

                    if (item.part_no || item.brand || item.description || item.qty || item.cost) {
                      items.push(item);
                    }
                });

                if (items.length === 0) {
                    Swal.fire('Error', 'Please enter at least one item.', 'error');
                    return;
                }

                if (hasEmpty) {
                    Swal.fire('Warning', 'Some rows have empty fields. Please fill them out or remove the rows.', 'warning');
                    return;
                }

                Swal.fire({
                    title: 'Save Beginning Inventory?',
                    text: `This will record ${items.length} items to the inventory for branch <?php echo $branch; ?>.`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#004d40',
                    cancelButtonColor: '#6e7881',
                    confirmButtonText: 'Yes, Save Now!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '../api/spareparts_inventory.php',
                            type: 'POST',
                            data: {
                                action: 'save_beginning_inventory',
                                items: JSON.stringify(items),
                                branch: '<?php echo $branch; ?>'
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        title: 'Success!',
                                        text: response.message,
                                        icon: 'success'
                                    }).then(() => {
                                        window.location.reload();
                                    });
                                } else {
                                    Swal.fire('Error', response.message, 'error');
                                }
                            },
                            error: function() {
                                Swal.fire('Error', 'Failed to communicate with the server.', 'error');
                            }
                        });
                    }
                });
            });

            $('#importExcelBtn').click(function() {
                Swal.fire({
                    title: 'Bulk Import (Excel Format)',
                    html: `
                        <div class="text-start">
                            <p class="small">Paste your Excel data here (Copy columns from Excel: Part Number, Brand, Description, Qty, Cost):</p>
                            <textarea id="excelPasteArea" class="form-control" rows="10" placeholder="Paste tab-separated data here..."></textarea>
                        </div>
                    `,
                    width: '600px',
                    showCancelButton: true,
                    confirmButtonText: 'Process Data',
                    confirmButtonColor: '#004d40',
                }).then((result) => {
                    if (result.isConfirmed) {
                        const rawData = $('#excelPasteArea').val();
                        const lines = rawData.split('\n');
                        let count = 0;
                        
                        $('#inventoryBody').empty();
                        
                        lines.forEach(line => {
                            if (line.trim()) {
                                const cols = line.split('\t');
                                if (cols.length >= 5) {
                                    addRow({
                                        part_no: cols[0].trim(),
                                        brand: cols[1].trim(),
                                        description: cols[2].trim(),
                                        qty: cols[3].trim(),
                                        cost: cols[4].trim()
                                    });
                                    count++;
                                }
                            }
                        });

                        if (count > 0) {
                            Swal.fire('Imported!', `Successfully processed ${count} rows.`, 'success');
                        } else {
                            Swal.fire('Error', 'No valid tab-separated data found. Make sure you copy from an Excel table.', 'error');
                            addRow();
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>
