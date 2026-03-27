<?php
require_once '../api/db_config.php';

// Check if user is logged in and has appropriate role
$allowedRoles = ['Spareparts-Admin', 'Spareparts-Owner', 'Spareparts-Warehouse', 'Spareparts-Sales'];
if (!isset($_SESSION['username']) || !in_array($_SESSION['position'], $allowedRoles)) {
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
    <title>Customer Beginning Balance - Spareparts MS</title>
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
            <span class="nav-brand-text">Customer Beginning Balance</span>
        </a>
        <div class="nav-right">
            <button onclick="history.back()" class="btn btn-outline-light btn-sm rounded-pill"><i class="bi bi-arrow-left"></i> Back</button>
        </div>
    </nav>

    <div class="hero-banner">
        <div class="hero-content">
            <h1 class="hero-greeting">Customer Beginning Balance</h1>
            <p class="hero-sub"><i class="bi bi-geo-alt"></i> Branch: <?php echo htmlspecialchars($branch); ?></p>
        </div>
    </div>

    <div class="main-content">
        <div class="excel-table-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="fw-bold mb-1">Balance Entry Table</h5>
                    <p class="text-muted small">Enter customer beginning balances here. Customer Name and Amount are required.</p>
                </div>
                <div class="d-flex gap-2">
                    <button id="addRowBtn" class="btn btn-outline-premium"><i class="bi bi-plus-circle"></i> Add Row</button>
                    <button id="importExcelBtn" class="btn btn-outline-premium"><i class="bi bi-file-earmark-excel"></i> Bulk Import</button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="excel-table" id="balanceTable">
                    <thead>
                        <tr>
                            <th>Customer Name</th>
                            <th style="width: 200px;">Ref / SI Number</th>
                            <th style="width: 180px;">Amount (₱)</th>
                            <th style="width: 180px;">As of Date</th>
                            <th style="width: 60px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="balanceBody">
                        <!-- Initial rows -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="sticky-footer shadow-lg">
        <div>
            <span class="text-muted fw-bold">Total Accounts: <span id="rowCountDisplay">0</span></span>
        </div>
        <div class="d-flex gap-3">
            <button id="clearAllBtn" class="btn btn-outline-danger px-4">Clear All</button>
            <button id="saveBalanceBtn" class="btn btn-premium px-5"><i class="bi bi-save"></i> Save Beginning Balances</button>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            let rowCount = 0;
            const today = new Date().toISOString().split('T')[0];

            function addRow(data = {}) {
                rowCount++;
                const row = `
                    <tr id="row-${rowCount}">
                        <td><input type="text" class="excel-input customer-name" placeholder="Customer Name" value="${data.customer_name || ''}"></td>
                        <td><input type="text" class="excel-input ref-no" placeholder="Optional SI/OR No" value="${data.ref_no || ''}"></td>
                        <td><input type="number" class="excel-input amount" placeholder="0.00" step="0.01" value="${data.amount || ''}"></td>
                        <td><input type="date" class="excel-input as-of-date" value="${data.date || today}"></td>
                        <td class="text-center">
                            <button class="btn-remove-row" onclick="removeRow(${rowCount})"><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>
                `;
                $('#balanceBody').append(row);
                updateCount();
            }

            window.removeRow = function(id) {
                if ($('#balanceBody tr').length > 1) {
                    $(`#row-${id}`).remove();
                    updateCount();
                } else {
                    Swal.fire('Error', 'At least one row is required.', 'error');
                }
            }

            function updateCount() {
                $('#rowCountDisplay').text($('#balanceBody tr').length);
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
                        $('#balanceBody').empty();
                        addRow();
                    }
                });
            });

            $('#saveBalanceBtn').click(function() {
                const items = [];
                let hasIncomplete = false;

                $('#balanceBody tr').each(function() {
                    const row = $(this);
                    const name = row.find('.customer-name').val().trim();
                    const amount = row.find('.amount').val().trim();
                    
                    if (name && amount) {
                        items.push({
                            customer_name: name,
                            ref_no: row.find('.ref-no').val().trim(),
                            amount: amount,
                            date: row.find('.as-of-date').val()
                        });
                    } else if (name || amount) {
                        hasIncomplete = true;
                    }
                });

                if (items.length === 0) {
                    Swal.fire('Error', 'Please enter at least one complete record (Name and Amount).', 'error');
                    return;
                }

                if (hasIncomplete) {
                    Swal.fire('Warning', 'Some rows are partially filled. These will be ignored or you can complete them.', 'warning');
                }

                Swal.fire({
                    title: 'Save Beginning Balances?',
                    text: `This will record ${items.length} accounts to the aging records for branch <?php echo $branch; ?>.`,
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
                                action: 'save_beginning_customer_balance',
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
                            <p class="small">Paste your Excel data here (Columns: Customer Name, Ref No, Amount, Date):</p>
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
                        
                        $('#balanceBody').empty();
                        
                        lines.forEach(line => {
                            if (line.trim()) {
                                const cols = line.split('\t');
                                if (cols.length >= 3) {
                                    addRow({
                                        customer_name: cols[0].trim(),
                                        ref_no: cols[1].trim(),
                                        amount: cols[2].trim(),
                                        date: cols[3] ? cols[3].trim() : today
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
