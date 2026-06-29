<?php
require_once '../api/db_config.php';

// Check if user is logged in and has appropriate role
$allowedRoles = ['Spareparts-Admin', 'Spareparts-Owner', 'Spareparts-Warehouse', 'Spareparts-Sales', 'Spareparts-Retail'];
if (!isset($_SESSION['username']) || !in_array($_SESSION['position'], $allowedRoles)) {
    header('Location: ../login.html');
    exit();
}

// Roles are checked in dashboards, but kept here for extra security
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
                    <a href="../api/spareparts_inventory.php?action=download_beginning_inventory_template" class="btn btn-outline-premium d-flex align-items-center">
                        <i class="bi bi-download me-2"></i>Download Template
                    </a>
                    <button id="addRowBtn" class="btn btn-outline-premium"><i class="bi bi-plus-circle"></i> Add Row</button>
                    <button class="btn btn-premium d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#bulkInventoryUploadModal">
                        <i class="bi bi-upload me-2"></i>Bulk Import
                    </button>
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

            function saveDraft() {
                const draft = [];
                $('#inventoryBody tr').each(function() {
                    const row = $(this);
                    draft.push({
                        part_no: row.find('.part-no').val() || '',
                        brand: row.find('.brand').val() || '',
                        description: row.find('.description').val() || '',
                        qty: row.find('.qty').val() || '',
                        cost: row.find('.cost').val() || ''
                    });
                });
                localStorage.setItem('beginning_inventory_draft', JSON.stringify(draft));
            }

            $(document).on('input', '.excel-input', function() {
                saveDraft();
            });

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
                    saveDraft();
                } else {
                    Swal.fire('Error', 'At least one row is required.', 'error');
                }
            }

            function updateCount() {
                $('#rowCountDisplay').text($('#inventoryBody tr').length);
            }

            // Load from draft or create 5 initial rows
            const draft = localStorage.getItem('beginning_inventory_draft');
            if (draft) {
                try {
                    const parsedDraft = JSON.parse(draft);
                    if (parsedDraft && parsedDraft.length > 0) {
                        parsedDraft.forEach(item => addRow(item));
                    } else {
                        for (let i = 0; i < 5; i++) addRow();
                    }
                } catch(e) {
                    for (let i = 0; i < 5; i++) addRow();
                }
            } else {
                for (let i = 0; i < 5; i++) addRow();
            }

            $('#addRowBtn').click(function() {
                addRow();
                saveDraft();
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
                        saveDraft();
                    }
                });
            });

            $('#saveInventoryBtn').click(function() {
                const items = [];
                let hasEmpty = false;

                $('#inventoryBody tr').each(function() {
                    const row = $(this);
                    const rawCost = row.find('.cost').val().trim();
                    const item = {
                        part_no: row.find('.part-no').val().trim(),
                        brand: row.find('.brand').val().trim(),
                        description: row.find('.description').val().trim(),
                        qty: row.find('.qty').val().trim(),
                        cost: rawCost === '' ? 0 : rawCost
                    };

                    if (item.part_no || item.brand || item.description || item.qty || rawCost !== '') {
                        if (!item.part_no || !item.brand || !item.description || !item.qty) {
                            hasEmpty = true;
                        }
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
                                    localStorage.removeItem('beginning_inventory_draft');
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

            // Form submit for preview
            $('#bulkInventoryForm').on('submit', function(e) {
                e.preventDefault();
                const fileInput = $('#bulkExcelFile')[0];
                if (fileInput.files.length === 0) return;

                const formData = new FormData();
                formData.append('excel_file', fileInput.files[0]);

                $('#btnPreviewBulk').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Loading...');

                $.ajax({
                    url: '../api/bulk_beginning_inventory_preview.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        $('#btnPreviewBulk').prop('disabled', false).html('<i class="bi bi-eye me-1"></i>Preview Changes');
                        if (response.success) {
                            const tbody = $('#bulkPreviewTbody');
                            tbody.empty();
                            
                            if (response.data.length === 0) {
                                tbody.append('<tr><td colspan="6" class="text-center py-4 text-muted">No valid items found to preview.</td></tr>');
                            } else {
                                response.data.forEach(item => {
                                    let statusClass = 'bg-success';
                                    if (item.status.includes('Existing')) {
                                        statusClass = 'bg-primary';
                                    } else if (item.status.includes('Invalid')) {
                                        statusClass = 'bg-danger';
                                    }
                                    
                                    const statusBadge = `<span class="badge ${statusClass}">${item.status}</span>`;
                                    tbody.append(`
                                        <tr>
                                            <td class="fw-bold ps-3">${item.part_no}</td>
                                            <td>${item.brand}</td>
                                            <td>${item.description}</td>
                                            <td class="text-end fw-semibold">${item.qty}</td>
                                            <td class="text-end fw-semibold text-success">₱${parseFloat(item.cost).toFixed(2)}</td>
                                            <td class="text-center pe-3">${statusBadge}</td>
                                        </tr>
                                    `);
                                });
                            }

                            $('#uploadView').addClass('d-none');
                            $('#previewView').removeClass('d-none');
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function() {
                        $('#btnPreviewBulk').prop('disabled', false).html('<i class="bi bi-eye me-1"></i>Preview Changes');
                        Swal.fire('Error', 'Failed to upload/preview the file.', 'error');
                    }
                });
            });

            // Back button in preview
            $('#btnBackToUpload').click(function() {
                $('#previewView').addClass('d-none');
                $('#uploadView').removeClass('d-none');
            });

            // Confirm bulk inventory save
            $('#confirmBulkBtn').click(function() {
                $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Applying...');

                $.ajax({
                    url: '../api/bulk_beginning_inventory_save.php',
                    type: 'POST',
                    dataType: 'json',
                    success: function(response) {
                        $('#confirmBulkBtn').prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i>Apply Updates');
                        if (response.success) {
                            bootstrap.Modal.getOrCreateInstance(document.getElementById('bulkInventoryUploadModal')).hide();
                            Swal.fire('Success!', response.message, 'success').then(() => {
                                localStorage.removeItem('beginning_inventory_draft');
                                window.location.reload();
                            });
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function() {
                        $('#confirmBulkBtn').prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i>Apply Updates');
                        Swal.fire('Error', 'Connection failed.', 'error');
                    }
                });
            });
        });
    </script>

    <!-- Bulk Inventory Upload Modal -->
    <div class="modal fade" id="bulkInventoryUploadModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 15px; overflow: hidden;">
                <div class="modal-header bg-success text-white border-0 py-3">
                    <h5 class="modal-title text-white fw-bold"><i class="bi bi-file-earmark-excel me-2"></i>Bulk Beginning Inventory Import</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <!-- Upload View -->
                    <div id="uploadView">
                        <div class="alert alert-success bg-success border-0 p-3 mb-4 text-white" style="border-radius: 10px;">
                            <h6 class="fw-bold text-white mb-2 d-flex align-items-center">
                                <i class="bi bi-info-circle-fill me-2 text-white"></i> Excel Format Instructions:
                            </h6>
                            <p class="mb-0 small text-white">Your file must include a header row with these columns: <strong class="text-white">Part Number</strong>, <strong class="text-white">Brand Name</strong>, <strong class="text-white">Description</strong>, <strong class="text-white">Qty</strong>, and <strong class="text-white">Cost</strong>.</p>
                        </div>
                        <form id="bulkInventoryForm" enctype="multipart/form-data">
                            <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
                                <div class="card-body p-4">
                                    <label for="bulkExcelFile" class="form-label fw-bold text-secondary small text-uppercase mb-2">Select Excel / CSV File</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-file-earmark-spreadsheet text-success"></i></span>
                                        <input type="file" class="form-control border-start-0 ps-0" id="bulkExcelFile" name="excel_file" accept=".xlsx, .xls, .csv" required>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-light px-4 fw-bold" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-success px-4 fw-bold text-white shadow-sm" id="btnPreviewBulk">
                                    <i class="bi bi-eye me-1"></i>Preview Changes
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Preview View (Hidden by default) -->
                    <div id="previewView" class="d-none">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="fw-bold mb-1 text-success d-flex align-items-center">
                                    <i class="bi bi-card-checklist me-2"></i>Updates Preview List
                                </h6>
                                <p class="small text-muted mb-0">Review the changes below before finalizing updates.</p>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary px-3 rounded-pill fw-semibold" id="btnBackToUpload">
                                <i class="bi bi-arrow-left me-1"></i>Back
                            </button>
                        </div>
                        <div class="table-responsive border-0 shadow-sm bg-white rounded-3 mb-4" style="max-height: 45vh; overflow-y: auto;">
                            <table class="table table-hover align-middle mb-0 small">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th class="py-3 ps-3">Part Number</th>
                                        <th class="py-3">Brand Name</th>
                                        <th class="py-3">Description</th>
                                        <th class="py-3 text-end">Qty</th>
                                        <th class="py-3 text-end">Cost</th>
                                        <th class="py-3 text-center pe-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody id="bulkPreviewTbody"></tbody>
                            </table>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-light px-4 fw-bold" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-success px-4 fw-bold text-white shadow-sm" id="confirmBulkBtn">
                                <i class="bi bi-check-circle me-1"></i>Apply Updates
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
