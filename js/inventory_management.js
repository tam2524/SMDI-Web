// =======================
// Global Variables
// =======================
let currentInventoryPage = 1;
let totalInventoryPages = 1;
let currentInventorySort = '';
let currentInventoryQuery = '';
let selectedRecordIds = [];
let hasShownIncomingTransfers = false; 
let shownTransferIds = [];
let lastCheckTime = new Date().toISOString();
let map;
let selectedMotorcycles = [];
let commonBranch = null;
let branchesMatch = true;
let currentReportData = null;
let currentReportMonth = null;
let currentReportBranch = null;

// =======================
// Document Ready
// =======================
$(document).ready(function() {
    shownTransferIds = [];
    
    loadInventoryDashboard();
    loadInventoryTable();
    setupEventListeners();
    loadBranchInventory(currentBranch);
    setInterval(checkIncomingTransfers, 1000);

    // Initialize map after a short delay to ensure DOM is ready
    setTimeout(() => { 
        if ($('#branchMap').length) {
            map = initMap(currentBranch); 
        }
    }, 300);
});

// =======================
// Event Listeners
// =======================
function setupEventListeners() {
    $('#searchModelBtn').click(searchModels);
    $('#searchModel').keypress(function(e) { if (e.which === 13) searchModels(); });

    $('#generateMonthlyInventory').click(showMonthlyInventoryOptions);
    $('#reportPeriod').change(toggleReportOptions);
    $('#generateReportBtn').click(generateMonthlyInventoryReport);
    $('#exportMonthlyReportToPDF').click(generateMonthlyReportPDF);
    $('#exportMonthlyReport').click(exportMonthlyReport);

    $('#transferSelectedBtn').prop('disabled', false);
    $('#transferSelectedBtn').click(transferSelectedMotorcycles);

    $('#addMotorcycleForm').submit(function(e) { e.preventDefault(); addMotorcycle(); });
    $('#editMotorcycleForm').submit(function(e) { e.preventDefault(); updateMotorcycle(); });
    $('#transferMotorcycleForm').submit(function(e) { e.preventDefault(); transferMotorcycle(); });

    $('#searchInventoryBtn').click(function() {
        currentInventoryQuery = $('#searchInventory').val();
        currentInventoryPage = 1;
        loadInventoryTable(currentInventoryPage, currentInventorySort, currentInventoryQuery);
    });

    $('#searchInventory').keypress(function(e) {
        if (e.which == 13) {
            currentInventoryQuery = $(this).val();
            currentInventoryPage = 1;
            loadInventoryTable(currentInventoryPage, currentInventorySort, currentInventoryQuery);
        }
    });

    $('#searchDashboardBtn').click(function() {
        loadInventoryDashboard($('#searchDashboard').val());
    });
    
    $('#searchDashboard').keypress(function(e) {
        if (e.which == 13) {
            loadInventoryDashboard($(this).val());
        }
    });

    $(document).on('click', '.page-link', function(e) {
        e.preventDefault();
        if ($(this).parent().hasClass('disabled')) return;
        const oldPage = currentInventoryPage;
        if ($(this).attr('id') === 'prevPage') {
            currentInventoryPage = Math.max(1, currentInventoryPage - 1);
        } else if ($(this).attr('id') === 'nextPage') {
            currentInventoryPage = Math.min(totalInventoryPages, currentInventoryPage + 1);
        } else {
            currentInventoryPage = parseInt($(this).data('page'));
        }
        if (currentInventoryPage !== oldPage) {
            loadInventoryTable(currentInventoryPage, currentInventorySort, currentInventoryQuery);
        }
    });

    $(document).on('click', '.sortable-header', function() {
        const sortField = $(this).data('sort');
        currentInventorySort = currentInventorySort === sortField + '_asc' ? sortField + '_desc' : sortField + '_asc';
        loadInventoryTable(currentInventoryPage, currentInventorySort, currentInventoryQuery);
    });

    $('#multipleTransferForm').submit(function(e) {
        e.preventDefault();
        performMultipleTransfers();
    });

    $('#multipleTransferModal').on('hidden.bs.modal', function() {
        selectedMotorcycles = [];
        updateSelectedMotorcyclesList();
        $('#engineSearch').val('');
        $('#searchResults').html('<div class="text-center text-muted py-3">Search for motorcycles using engine number</div>');
    });

    $('#acceptAllTransfersBtn').click(function() {
    const transferIds = [];
    $('#incomingTransfersBody tr').each(function() {
        const id = $(this).data('transfer-id');
        if (id) transferIds.push(id);
    });
    
    if (transferIds.length === 0) {
        showErrorModal('No transfers to accept');
        return;
    }
    
    $.ajax({
        url: '../api/inventory_management.php',
        method: 'POST',
        data: {
            action: 'accept_transfers',
            transfer_ids: transferIds.join(','),
            current_branch: currentBranch
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showSuccessModal(response.message || 'Transfers accepted successfully!');
                $('#incomingTransfersModal').modal('hide');
                
                // Wait for the success modal to show, then reload the page
                setTimeout(function() {
                    window.location.reload();
                }, 2000); // Reload after 2 seconds (same as success modal timeout)
                
                hasShownIncomingTransfers = false;
            } else {
                showErrorModal(response.message || 'Error accepting transfers');
            }
        },
        error: function(xhr, status, error) {
            showErrorModal('Error accepting transfers: ' + error);
        }
    });
});

    $(document).on('hidden.bs.modal', '#incomingTransfersModal', function () {
    hasShownIncomingTransfers = false;
});
    $('#searchEngineBtn').click(searchMotorcyclesByEngine);
    $('#engineSearch').keypress(function(e) {
        if (e.which == 13) {
            searchMotorcyclesByEngine();
            e.preventDefault();
        }
    });

    $('#clearSearchBtn').click(function() {
    $('#engineSearch').val('');
    $('#searchResults').html(`
        <div class='text-center text-muted py-4'>
            <i class='bi bi-search display-6 text-muted mb-2'></i>
            <p>Search for motorcycles to display results</p>
        </div>
    `);
    $('#searchResultsCount').text('0');
});

    $('#clearSelectionBtn').click(function() {
    selectedMotorcycles = [];
    updateSelectedMotorcyclesList();
    $('#searchResults .transfer-search-result').removeClass('selected');
    $('#searchResults .select-btn')
        .removeClass('btn-danger')
        .addClass('btn-success')
        .text('Select');
});
}

// =======================
// Modal Functions
// =======================
function showSuccessModal(message) {
    $('#successMessage').text(message);
    $('#successModal').modal('show');
    setTimeout(() => { $('#successModal').modal('hide'); }, 2000);
}

function showErrorModal(message) {
    $('#errorMessage').text(message);
    $('#errorModal').modal('show');
    setTimeout(() => { $('#errorModal').modal('hide'); }, 3000);
}

function showConfirmationModal(message, title, callback) {
    $('#confirmationMessage').text(message);
    $('#confirmationModalLabel').text(title);
    const modal = $('#confirmationModal');
    modal.off('click', '#confirmActionBtn');
    modal.on('click', '#confirmActionBtn', function() {
        modal.modal('hide');
        if (typeof callback === 'function') callback();
    });
    modal.modal('show');
}

// =======================
// Inventory Table & Pagination
// =======================
function loadInventoryDashboard(searchTerm = '', sortBy = 'model', sortOrder = 'asc') {
    $('#inventoryCards').html('<div class="col-12 text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
    
    $.ajax({
        url: '../api/inventory_management.php',
        method: 'GET',
        data: {
            action: 'get_inventory_dashboard',
            search: searchTerm,
            include_brand: true
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                let sortedData = response.data;
                
                // Sort the data based on the specified criteria
                sortedData.sort((a, b) => {
                    let valueA, valueB;
                    
                    if (sortBy === 'model') {
                        valueA = a.model.toLowerCase();
                        valueB = b.model.toLowerCase();
                    } else if (sortBy === 'brand') {
                        valueA = a.brand.toLowerCase();
                        valueB = b.brand.toLowerCase();
                    } else {
                        // Default to model sorting
                        valueA = a.model.toLowerCase();
                        valueB = b.model.toLowerCase();
                    }
                    
                    if (valueA < valueB) return sortOrder === 'asc' ? -1 : 1;
                    if (valueA > valueB) return sortOrder === 'asc' ? 1 : -1;
                    return 0;
                });
                
                renderInventoryCards(sortedData);
            } else {
                $('#inventoryCards').html('<div class="col-12 text-center py-5 text-danger">Error loading inventory data</div>');
                showErrorModal(response.message || 'Error loading dashboard data');
            }
        },
        error: function(xhr, status, error) {
            $('#inventoryCards').html('<div class="col-12 text-center py-5 text-danger">Error loading inventory data: ' + error + '</div>');
            showErrorModal('Error loading dashboard: ' + error);
        }
    });
}
function renderInventoryCards(data) {
    let html = '';
    
    if (data.length === 0) {
        html = '<div class="col-12 text-center py-5 text-muted">No inventory data found</div>';
    } else {
        // Group models by brand
        const brands = {};
        data.forEach(item => {
            if (!brands[item.brand]) {
                brands[item.brand] = [];
            }
            brands[item.brand].push(item);
        });

        // Render models grouped by brand (without brand headers)
        for (const brand in brands) {
            // Determine brand color
            let brandColor = '';
            switch(brand.toLowerCase()) {
                case 'suzuki': brandColor = 'border-primary bg-primary-light'; break;
                case 'honda': brandColor = 'border-danger bg-danger-light'; break;
                case 'yamaha': brandColor = 'border-black bg-black-light'; break;
                case 'kawasaki': brandColor = 'border-success bg-success-light'; break;
                default: brandColor = 'border-secondary bg-secondary-light';
            }

            // Add models for this brand with tighter spacing
            brands[brand].forEach(item => {
                html += `
                    <div class="col-xl-1 col-lg-2 col-md-3 col-sm-4 col-6 model-card-container px-1 mb-2">
                        <div class="model-card d-flex justify-content-between align-items-center ${brandColor}">
                            <div class="model-name" title="${item.model}">${item.model}</div>
                            <div class="quantity-badge">${item.total_quantity}</div>
                        </div>
                    </div>
                `;
            });
        }
    }
    
    $('#inventoryCards').html(html);
}
function loadInventoryTable(page = 1, sort = '', query = '') {
    $('#inventoryTableBody').html('<tr><td colspan="11" class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>');
    
    $.ajax({
        url: '../api/inventory_management.php',
        method: 'GET',
        data: {
            action: 'get_inventory_table',
            page: page,
            sort: sort,
            query: query
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                currentInventoryPage = page;
                totalInventoryPages = response.pagination.totalPages || 1; // Changed to use response.pagination.totalPages
                renderInventoryTable(response.data);
                updateInventoryPaginationControls(totalInventoryPages);
            } else {
                $('#inventoryTableBody').html('<tr><td colspan="11" class="text-center py-5 text-danger">Error loading inventory data</td></tr>');
                showErrorModal(response.message || 'Error loading table data');
            }
        },
        error: function(xhr, status, error) {
            $('#inventoryTableBody').html('<tr><td colspan="11" class="text-center py-5 text-danger">Error loading inventory data: ' + error + '</td></tr>');
            showErrorModal('Error loading table: ' + error);
        }
    });
}

function updateInventoryPaginationControls(totalPages) {
    let paginationHtml = '';
    const maxVisiblePages = 5;
    let startPage, endPage;
    
    if (totalPages <= maxVisiblePages) {
        startPage = 1;
        endPage = totalPages;
    } else {
        const half = Math.floor(maxVisiblePages / 2);
        if (currentInventoryPage <= half + 1) {
            startPage = 1;
            endPage = maxVisiblePages;
        } else if (currentInventoryPage >= totalPages - half) {
            startPage = totalPages - maxVisiblePages + 1;
            endPage = totalPages;
        } else {
            startPage = currentInventoryPage - half;
            endPage = currentInventoryPage + half;
        }
    }
    
    // Previous button
    paginationHtml += `
        <li class="page-item ${currentInventoryPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" id="prevPage">
                <i class="fas fa-chevron-left me-1"></i> Previous
            </a>
        </li>`;
    
    // First page + ellipsis
    if (startPage > 1) {
        paginationHtml += `
            <li class="page-item">
                <a class="page-link" href="#" data-page="1">1</a>
            </li>`;
        if (startPage > 2) {
            paginationHtml += `
                <li class="page-item disabled">
                    <span class="page-link">...</span>
                </li>`;
        }
    }
    
    // Page numbers
    for (let i = startPage; i <= endPage; i++) {
        paginationHtml += `
            <li class="page-item ${currentInventoryPage === i ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>`;
    }
    
    // Last page + ellipsis
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            paginationHtml += `
                <li class="page-item disabled">
                    <span class="page-link">...</span>
                </li>`;
        }
        paginationHtml += `
            <li class="page-item">
                <a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a>
            </li>`;
    }
    
    // Next button
    paginationHtml += `
        <li class="page-item ${currentInventoryPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" id="nextPage">
                Next <i class="fas fa-chevron-right ms-1"></i>
            </a>
        </li>`;
    
    $('#paginationControls').html(paginationHtml);
}

function renderInventoryTable(data) {
    let html = '';
    
    if (data.length === 0) {
        html = '<tr><td colspan="11" class="text-center py-5 text-muted">No inventory data found</td></tr>';
    } else {
        data.forEach(item => {
            html += `
                <tr data-id="${item.id}">
                <td>${item.invoice_number || 'N/A'}</td>
                    <td>${formatDate(item.date_delivered)}</td>
                    <td>${item.brand}</td>
                    <td>${item.model}</td>
                    <td>${item.engine_number}</td>
                    <td>${item.frame_number}</td>
                    <td>${item.color}</td>
                    <td>${formatCurrency(item.lcp)}</td>
                    <td>${item.current_branch}</td>
                  
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary edit-btn">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-outline-info transfer-btn">
                                <i class="bi bi-truck"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
    }
    
    $('#inventoryTableBody').html(html);
    setupTableActionButtons();
}

function setupTableActionButtons() {
    $('.edit-btn').click(function() {
        const id = $(this).closest('tr').data('id');
        loadMotorcycleForEdit(id);
    });
    $('.return-btn').click(function() {
        const id = $(this).closest('tr').data('id');
        showConfirmationModal(
            'Are you sure you want to return this motorcycle to Head Office?', 
            'Return Motorcycle',
            function() { returnToHeadOffice(id); }
        );
    });
    $('.transfer-btn').click(function() {
        const id = $(this).closest('tr').data('id');
        loadMotorcycleForTransfer(id);
    });
}

function getStatusBadgeClass(status) {
    switch(status) {
        case 'available': return 'bg-success';
        case 'sold': return 'bg-danger';
        case 'transferred': return 'bg-warning text-dark';
        default: return 'bg-secondary';
    }
}

// =======================
// Motorcycle CRUD
// =======================
function addMotorcycle() {
     const formData = {
        action: 'add_motorcycle',
        date_delivered: $('#dateDelivered').val(),
        brand: $('#brand').val(),
        model: $('#model').val(),
        engine_number: $('#engineNumber').val(),
        frame_number: $('#frameNumber').val(),
        invoice_number: $('#invoiceNumber').val(), 
        color: $('#color').val(),
        quantity: $('#quantity').val(),
        lcp: $('#lcp').val(),
        current_branch: $('#currentBranch').val()
    };
    
    // Validate required fields
    if (!formData.date_delivered || !formData.brand || !formData.model || 
        !formData.engine_number || !formData.frame_number || !formData.color) {
        showErrorModal('Please fill in all required fields');
        return;
    }
    
    $.ajax({
        url: '../api/inventory_management.php',
        method: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Hide modal and remove backdrop
                $('#addMotorcycleModal').modal('hide');
                
                // Manually remove any leftover backdrop
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open');
                
                $('#addMotorcycleForm')[0].reset();
                showSuccessModal('Motorcycle added successfully!');
                
                loadInventoryDashboard();
                loadInventoryTable(currentInventoryPage, currentInventorySort, currentInventoryQuery);
            } else {
                showErrorModal(response.message || 'Error adding motorcycle');
            }
        },
        error: function(xhr, status, error) {
            showErrorModal('Error adding motorcycle: ' + error);
        }
    });
}

function updateMotorcycle() {
       const formData = {
        action: 'update_motorcycle',
        id: $('#editId').val(),
        date_delivered: $('#editDateDelivered').val(),
        brand: $('#editBrand').val(),
        model: $('#editModel').val(),
        engine_number: $('#editEngineNumber').val(),
        frame_number: $('#editFrameNumber').val(),
        invoice_number: $('#editInvoiceNumber').val(), // NEW FIELD
        color: $('#editColor').val(),
        lcp: $('#editLcp').val(),
        current_branch: $('#editCurrentBranch').val(),
        status: $('#editStatus').val()
    };
    
    // Validate required fields
    if (!formData.id || !formData.date_delivered || !formData.brand || !formData.model || 
        !formData.engine_number || !formData.frame_number || !formData.color) {
        showErrorModal('Please fill in all required fields');
        return;
    }
    
    $.ajax({
        url: '../api/inventory_management.php',
        method: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#editMotorcycleModal').modal('hide');
                showSuccessModal('Motorcycle updated successfully!');
                loadInventoryTable(currentInventoryPage, currentInventorySort, currentInventoryQuery);
            } else {
                showErrorModal(response.message || 'Error updating motorcycle');
            }
        },
        error: function(xhr, status, error) {
            showErrorModal('Error updating motorcycle: ' + error);
        }
    });
}

function loadMotorcycleForEdit(id) {
    $.ajax({
        url: '../api/inventory_management.php',
        method: 'GET',
        data: {
            action: 'get_motorcycle',
            id: id
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const data = response.data;
                $('#editId').val(data.id);
                $('#editDateDelivered').val(data.date_delivered);
                $('#editBrand').val(data.brand);
                $('#editModel').val(data.model);
                $('#editEngineNumber').val(data.engine_number);
                $('#editFrameNumber').val(data.frame_number);
                $('#editInvoiceNumber').val(data.invoice_number || ''); // NEW FIELD
                $('#editColor').val(data.color);
                $('#editLcp').val(data.lcp);
                $('#editCurrentBranch').val(data.current_branch);
                $('#editStatus').val(data.status);
                
                $('#editMotorcycleModal').modal('show');
            } else {
                showErrorModal(response.message || 'Error loading motorcycle data');
            }
        },
        error: function(xhr, status, error) {
            showErrorModal('Error loading motorcycle: ' + error);
        }
    });
}

function loadMotorcycleForTransfer(id) {
    $.ajax({
        url: '../api/inventory_management.php',
        method: 'GET',
        data: {
            action: 'get_motorcycle',
            id: id
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const data = response.data;
                $('#transferId').val(data.id);
                $('#fromBranch').val(data.current_branch);
                
                const $toBranch = $('#toBranch');
                $toBranch.empty().append('<option value="">Select Branch</option>');
                
                const branches = ['HEADOFFICE', 'RXS-S', 'RXS-H', 'ANT-1', 'ANT-2', 'SDH', 'SDS', 'JAR-1', 'JAR-2', 'SKM', 'SKS', 'ALTA', 'EMAP', 'CUL', 'BAC', 'PAS-1', 'PAS-2', 'BAL', 'GUIM', 'PEMDI', 'EEM', 'AJU', 'BAIL', 'MINDORO MB', 'MINDORO 3S', 'MANSALAY', 'K-RIDERS', 'IBAJAY', 'NUMANCIA', 'CEBU'];
                
                branches.forEach(branch => {
                    if (branch !== data.current_branch) {
                        $toBranch.append(`<option value="${branch}">${branch}</option>`);
                    }
                });
                
                $('#transferDate').val(new Date().toISOString().split('T')[0]);
                $('#transferMotorcycleModal').modal('show');
            } else {
                showErrorModal(response.message || 'Error loading motorcycle data');
            }
        },
        error: function(xhr, status, error) {
            showErrorModal('Error loading motorcycle: ' + error);
        }
    });
}

function transferMotorcycle() {
    const formData = {
        action: 'transfer_motorcycle',
        motorcycle_id: $('#transferId').val(),
        from_branch: $('#fromBranch').val(),
        to_branch: $('#toBranch').val(),
        transfer_date: $('#transferDate').val(),
        notes: $('#transferNotes').val()
    };
    
    // Validate required fields
    if (!formData.motorcycle_id || !formData.from_branch || !formData.to_branch || !formData.transfer_date) {
        showErrorModal('Please fill in all required fields');
        return;
    }
    
    if (formData.from_branch === formData.to_branch) {
        showErrorModal('Cannot transfer to the same branch');
        return;
    }
    
    $.ajax({
        url: '../api/inventory_management.php',
        method: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#transferMotorcycleModal').modal('hide');
                showSuccessModal('Motorcycle transferred successfully!');
                loadInventoryTable(currentInventoryPage, currentInventorySort, currentInventoryQuery);
            } else {
                showErrorModal(response.message || 'Error transferring motorcycle');
            }
        },
        error: function(xhr, status, error) {
            showErrorModal('Error transferring motorcycle: ' + error);
        }
    });
}


// =======================
// Transfer Functions
// =======================
function transferSelectedMotorcycles() {
    
    // Populate the modal
    $('#multipleFromBranch').val(currentBranch);
    $('#multipleTransferDate').val(new Date().toISOString().split('T')[0]);
    $('#selectedCount').text('0');
    
    // Clear previous selections
    selectedMotorcycles = [];
    updateSelectedMotorcyclesList();
    $('#engineSearch').val('');
    $('#searchResults').html(`
        <div class='text-center text-muted py-4'>
            <i class='bi bi-search display-6 text-muted mb-2'></i>
            <p>Search for motorcycles to display results</p>
        </div>
    `);
    $('#searchResultsCount').text('0');
    
    // Populate toBranch dropdown
    const $toBranch = $('#multipleToBranch');
    $toBranch.empty().append('<option value="">Select Destination Branch</option>');
    
    const branches = ['HEADOFFICE','RXS-S', 'RXS-H', 'ANT-1', 'ANT-2', 'SDH', 'SDS', 'JAR-1', 'JAR-2', 'SKM', 'SKS', 'ALTA', 'EMAP', 'CUL', 'BAC', 'PAS-1', 'PAS-2', 'BAL', 'GUIM', 'PEMDI', 'EEM', 'AJU', 'BAIL', 'MINDORO MB', 'MINDORO 3S', 'MANSALAY', 'K-RIDERS', 'IBAJAY', 'NUMANCIA',  'CEBU'];
    
    branches.forEach(branch => {
        if (branch !== currentBranch) {
            $toBranch.append(`<option value="${branch}">${branch}</option>`);
        }
    });
    
    // Show the modal
    $('#multipleTransferModal').modal('show');
}
function performMultipleTransfers() {
    // Get IDs from the selectedMotorcycles array instead of modal data
    const selectedIds = selectedMotorcycles.map(m => m.id);
    
    if (selectedIds.length === 0) {
        showErrorModal('Please select at least one motorcycle to transfer');
        return;
    }
    
    const formData = {
        action: 'transfer_multiple_motorcycles',
        motorcycle_ids: selectedIds.join(','),
        from_branch: $('#multipleFromBranch').val(),
        to_branch: $('#multipleToBranch').val(),
        transfer_date: $('#multipleTransferDate').val(),
        notes: $('#multipleTransferNotes').val()
    };
    
    // Validate required fields
    if (!formData.motorcycle_ids || !formData.from_branch || !formData.to_branch || !formData.transfer_date) {
        showErrorModal('Please fill in all required fields');
        return;
    }
    
    if (formData.from_branch === formData.to_branch) {
        showErrorModal('Cannot transfer to the same branch');
        return;
    }
    
     $.ajax({
        url: '../api/inventory_management.php',
        method: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#multipleTransferModal').modal('hide');
                showSuccessModal('Transfer initiated successfully! Motorcycles will remain at current branch until accepted by destination.');
                loadInventoryTable(currentInventoryPage, currentInventorySort, currentInventoryQuery);
                
                // Clear selection
                selectedMotorcycles = [];
                updateSelectedMotorcyclesList();
                $('#engineSearch').val('');
                $('#searchResults').html('<div class="text-center text-muted py-3">Search for motorcycles using engine number</div>');
            } else {
                showErrorModal(response.message || 'Error initiating transfer');
            }
        },
        error: function(xhr, status, error) {
            showErrorModal('Error initiating transfer: ' + error);
        }
    });
}
function searchMotorcyclesByEngine() {
    const searchTerm = $('#engineSearch').val().trim();
    
    if (!searchTerm) {
        showErrorModal('Please enter an engine number to search');
        return;
    }
    
    $.ajax({
        url: '../api/inventory_management.php',
        method: 'GET',
        data: {
            action: 'search_inventory_by_engine',
            query: searchTerm,
            field: 'engine_number',
            include_lcp: true,
            fuzzy_search: true
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                if (response.data.length === 0) {
                    // Show message that no matching motorcycles found in current branch
                    $('#searchResults').html(`
                        <div class='text-center text-muted py-4'>
                            <i class='bi bi-search display-6 text-muted mb-2'></i>
                            <p>No matching motorcycles found in ${currentBranch} branch</p>
                        </div>
                    `);
                } else {
                    displaySearchResults(response.data);
                }
            } else {
                showErrorModal(response.message || 'Error searching motorcycles');
            }
        },
        error: function(xhr, status, error) {
            showErrorModal('Error searching motorcycles: ' + error);
        }
    });
}
function displaySearchResults(data) {
    const $resultsContainer = $('#searchResults');
    $('#searchResultsCount').text(data.length);
    
    if (data.length === 0) {
        $resultsContainer.html(`
            <div class='text-center text-muted py-4'>
                <i class='bi bi-search display-6 text-muted mb-2'></i>
                <p>No motorcycles found</p>
            </div>
        `);
        return;
    }
    
    let html = '';
    data.forEach(motorcycle => {
        const isSelected = selectedMotorcycles.some(m => m.id === motorcycle.id);
        const lcpValue = motorcycle.lcp ? formatCurrency(motorcycle.lcp) : 'N/A';
        
        html += `
            <div class="transfer-search-result ${isSelected ? 'selected' : ''}" 
                 onclick="toggleMotorcycleSelection(${motorcycle.id}, '${motorcycle.engine_number}', '${motorcycle.brand}', '${motorcycle.model}', '${motorcycle.color}', '${motorcycle.current_branch}', ${motorcycle.lcp || 0})">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="engine-number">${motorcycle.engine_number}</div>
                        <div class="model-info">${motorcycle.brand} ${motorcycle.model} - ${motorcycle.color}</div>
                       <div class="lcp-info small text-success">
   LCP: ${lcpValue}
</div>

                        <div class="branch-info">
                            <i class="bi bi-geo-alt me-1"></i>${motorcycle.current_branch}
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm ${isSelected ? 'btn-danger' : 'btn-success'} select-btn ms-2"
                            onclick="event.stopPropagation(); toggleMotorcycleSelection(${motorcycle.id}, '${motorcycle.engine_number}', '${motorcycle.brand}', '${motorcycle.model}', '${motorcycle.color}', '${motorcycle.current_branch}', ${motorcycle.lcp || 0})">
                        ${isSelected ? 'Remove' : 'Select'}
                    </button>
                </div>
            </div>
        `;
    });
    
    $resultsContainer.html(html);
}

function toggleMotorcycleSelection(id, engineNumber, brand, model, color, currentBranch, lcp = 0) {
    const index = selectedMotorcycles.findIndex(m => m.id === id);
    
    if (index === -1) {
        // Add to selection
        selectedMotorcycles.push({
            id: id,
            engine_number: engineNumber,
            brand: brand,
            model: model,
            color: color,
            current_branch: currentBranch,
            lcp: lcp || 0
        });
    } else {
        // Remove from selection
        selectedMotorcycles.splice(index, 1);
    }
    
    updateSelectedMotorcyclesList();
    updateTransferSummary();
    // Refresh search results to update button states
    searchMotorcyclesByEngine();
}

function updateTransferSummary() {
    const $selectedCount = $('#selectedCount');
    const $totalLcpValue = $('#totalLcpValue');
    const $selectionProgress = $('#selectionProgress');
    
    // Update counts
    $selectedCount.text(selectedMotorcycles.length);
    
    // Calculate total LCP
    const totalLcp = selectedMotorcycles.reduce((sum, motorcycle) => sum + (parseFloat(motorcycle.lcp) || 0), 0);
    $totalLcpValue.text(formatCurrency(totalLcp));
    
    // Update progress bar (optional visual indicator)
    const progressPercentage = Math.min((selectedMotorcycles.length / 10) * 100, 100);
    $selectionProgress.css('width', progressPercentage + '%');
}

function updateSelectedMotorcyclesList() {
    const $selectedList = $('#selectedMotorcyclesList');
    
    if (selectedMotorcycles.length === 0) {
        $selectedList.html(`
            <div class='text-center text-muted py-4'>
                <i class='bi bi-inbox display-6 text-muted mb-2'></i>
                <p>No motorcycles selected</p>
            </div>
        `);
        return;
    }
    
    let html = '';
    selectedMotorcycles.forEach((motorcycle, index) => {
        const lcpValue = motorcycle.lcp ? formatCurrency(motorcycle.lcp) : 'N/A';
        
        html += `
            <div class="selected-motorcycle-item">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center mb-1">
                            <span class="badge bg-primary me-2">${index + 1}</span>
                            <span class="fw-semibold text-primary">${motorcycle.engine_number}</span>
                        </div>
                        <div class="small text-muted mb-1">${motorcycle.brand} ${motorcycle.model} - ${motorcycle.color}</div>
                        <div class="small text-success mb-1">
                          LCP: ${lcpValue}
                        </div>
                        <div class="small">
                            <i class="bi bi-geo-alt me-1"></i>${motorcycle.current_branch}
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger" 
                            onclick="removeMotorcycleFromSelection(${motorcycle.id})">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            </div>
        `;
    });
    
    $selectedList.html(html);
}
function removeMotorcycleFromSelection(id) {
    const index = selectedMotorcycles.findIndex(m => m.id === id);
    if (index !== -1) {
        selectedMotorcycles.splice(index, 1);
        updateSelectedMotorcyclesList();
        updateTransferSummary(); // Update summary when removing items
        // Refresh search results to update button states
        searchMotorcyclesByEngine();
    }
}
// =======================
// Incoming Transfers
// =======================
function checkIncomingTransfers() {
    if (!currentBranch) {
        console.error('Current branch not set');
        return;
    }

    $.ajax({
        url: '../api/inventory_management.php',
        method: 'GET',
        data: {
            action: 'get_incoming_transfers',
            last_check_time: lastCheckTime,
            current_branch: currentBranch
        },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data.length > 0) {
                // Filter out transfers we've already shown
                const newTransfers = response.data.filter(transfer => 
                    !shownTransferIds.includes(transfer.transfer_id)
                );
                
                if (newTransfers.length > 0) {
                    showIncomingTransfersModal(newTransfers);
                    // Update shown transfer IDs
                    newTransfers.forEach(transfer => {
                        shownTransferIds.push(transfer.transfer_id);
                    });
                    // Update the last check time
                    lastCheckTime = new Date().toISOString();
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching incoming transfers:', error);
        }
    });
}
function showIncomingTransfersModal(transfers) {
    const tbody = $('#incomingTransfersBody');
    tbody.empty();
    
    if (transfers.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="6" class="text-center py-4 text-muted">No incoming transfers found</td>
            </tr>
        `);
    } else {
        transfers.forEach(transfer => {
            tbody.append(`
                <tr data-transfer-id="${transfer.transfer_id}">
                    <td>${transfer.brand} ${transfer.model}</td>
                    <td>${transfer.engine_number}</td>
                    <td>${transfer.frame_number}</td>
                    <td>${transfer.color}</td>
                    <td>${formatDate(transfer.transfer_date)}</td>
                    <td>${transfer.from_branch}</td>
                </tr>
            `);
        });
    }
    
    // Show the modal if not already shown
    if (!hasShownIncomingTransfers) {
        $('#incomingTransfersModal').modal('show');
        hasShownIncomingTransfers = true;
    }
}

// =======================
// Branch Inventory & Map
// =======================
function initMap(currentBranch) {
    const map = L.map('branchMap').setView([11.5852, 122.7511], 10);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    $.get('../api/inventory_management.php', {
        action: 'get_branches_with_inventory'
    }, function(response) {
        if (response.success) {
            const branchCoordinates = {
                'RXS-S': { lat: 11.581639063474135, lng: 122.75283046163139 },
                'RXS-H': { lat: 11.591933174094493, lng: 122.75177370058198 },
                'ANT-1': { lat: 10.747081312946916, lng: 121.94138590805788 },
                'ANT-2': { lat: 10.749653220828158, lng: 121.94142882340054 },
                'SDH': { lat: 10.697818450677735, lng: 122.56464019830032 },
                'SDS': { lat: 10.721591441858077, lng: 122.55598339171726 },
                'JAR-1': { lat: 10.746529482552543, lng: 122.56703172463938 },
                'JAR-2': { lat: 10.749878260560397, lng: 122.56812797163823 },
                'SKM': { lat: 11.726705198816557, lng: 122.36889838061255 },
                'SKS': { lat: 11.702856917692344, lng: 122.36675785507218 },
                'ALTA': { lat: 11.581991439599044, lng: 122.75273929376398 },
                'EMAP': { lat: 11.581991439599044, lng: 122.75273929376398 },
                'CUL': { lat: 11.428798698065513, lng: 122.05695055376913 },
                'BAC': { lat: 10.670965032727254, lng: 122.95977720190973 },
                'PAS-1': { lat: 11.105396570048141, lng: 122.64601950262048 },
                'PAS-2': { lat: 11.106284551766606, lng: 122.64677038445016 },
                'BAL': { lat: 11.46865937405874, lng: 123.09560889637078 },
                'GUIM': { lat: 10.605846163901681, lng: 122.58799192677242 },
                'PEMDI': { lat: 10.65556975930108, lng: 122.93918296725195 },
                'EEM': { lat: 10.605758954854227, lng: 122.58813091469503 },
                'AJU': { lat: 11.179194176167435, lng: 123.01975649183555 },
                'BAIL': { lat: 11.450895697343983, lng: 122.82968507428964 },
                '3SMB': { lat: 12.602606955880981, lng: 121.5037542414926 },
                '3SMIN': { lat: 12.371133617009118, lng: 121.06330210820141 },
                'MAN': { lat: 12.530846939769289, lng: 121.44707141396867 },
                'K-RIDERS': { lat: 11.626344148372608, lng: 122.73960109140822 },
                'IBAJAY': { lat: 11.815513408059678, lng: 122.15988390959608 },
                'NUMANCIA': { lat: 11.716374415728836, lng: 122.35946468260876 },
                'HEADOFFICE': { lat: 11.58156063320175, lng: 122.75277786727027 },
                'CEBU': { lat: 10.315699, lng: 123.885437 }
            };

            response.data.forEach(branch => {
                if (branch.total_quantity > 0) {
                    const coord = branchCoordinates[branch.branch] || { lat: 11.5852, lng: 122.7511 };
                    const isCurrent = branch.branch === currentBranch;
                    
                    const marker = L.marker([coord.lat, coord.lng], {
                        icon: L.divIcon({
                            className: `branch-marker ${isCurrent ? 'current-branch' : ''}`,
                            html: branch.branch.substring(0, 2),
                            iconSize: [30, 30]
                        })
                    }).addTo(map);
                    
                    marker.bindPopup(`
                        <b>Branch ${branch.branch}</b><br>
                        <small>${branch.total_quantity} units available</small>
                    `);
                    
                    marker.on('click', function() {
                        loadBranchInventory(branch.branch);
                    });
                }
            });
        }
    }, 'json');

    return map;
}

function loadBranchInventory(branchCode) {
    $('#branchInfo').html(`<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>`);
    $('#modelList').empty();
    
    $.get('../api/inventory_management.php', {
        action: 'get_branch_inventory',
        branch: branchCode,
        status: 'all' 
    }, function(response) {
        if (response.success && response.data.length > 0) {
            $('#branchInfo').html(`
                <h6>Branch: <strong>${branchCode}</strong></h6>
                <p class="small">${response.data.length} units available</p>
            `);
            
            const modelGroups = groupByModel(response.data);
            let html = '';
            
            Object.keys(modelGroups).forEach(model => {
                const items = modelGroups[model];
                html += `
                    <div class="card mb-2 model-item" data-model="${model}">
                        <div class="card-body">
                            <h6 class="card-title">${model}</h6>
                            <p class="card-text small">
                                ${items.length} available · 
                                ${items[0].color} · 
                                ${items[0].current_branch}
                            </p>
                        </div>
                    </div>
                `;
            });
            
            $('#modelList').html(html);
            
            $('.model-item').click(function() {
                const model = $(this).data('model');
                viewModelDetails(modelGroups[model][0].id);
            });
        } else {
            $('#branchInfo').html(`
                <h6>Branch: <strong>${branchCode}</strong></h6>
                <p class="text-muted">No inventory available</p>
            `);
            $('#modelList').html('<p class="text-muted">No models found</p>');
        }
    }, 'json');
}
function viewModelDetails(id) {
    $('#motorcycleDetails').html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>');
    
    const detailsModalEl = document.getElementById('detailsModal');
    const detailsModal = new bootstrap.Modal(detailsModalEl);

    $.get('../api/inventory_management.php', {
        action: 'get_motorcycle',
        id: id
    }, function(response) {
        if (response.success) {
            const item = response.data;
            let detailsHTML = `
            
                <h6>${item.brand} ${item.model}</h6>
                <p><strong>Color:</strong> ${item.color}</p>
                <p><strong>Current Branch:</strong> ${item.current_branch}</p>
                <p><strong>Status:</strong> <span class="badge ${getStatusClass(item.status)}">
                    ${item.status.charAt(0).toUpperCase() + item.status.slice(1)}
                </span></p>
                <hr>
                <p><strong>Engine #:</strong> ${item.engine_number}</p>
                <p><strong>Frame #:</strong> ${item.frame_number}</p>
                <p><strong>Date Delivered:</strong> ${item.date_delivered}</p>
            `;

            if (item.latitude && item.longitude) {
                detailsHTML += `
                    <hr>
                    <h6>Location</h6>
                    <div id="mapid" style="height: 300px;"></div>
                `;
            }

            $('#motorcycleDetails').html(detailsHTML);

            // Remove old modal event listener
            $(detailsModalEl).off('shown.bs.modal');

            // Attach new listener
            if (item.latitude && item.longitude) {
                $(detailsModalEl).on('shown.bs.modal', function () {
                    const container = document.getElementById('mapid');
                    if (!container) return; // safety guard

                    // Reset Leaflet if reopened
                    if (container._leaflet_id) {
                        container._leaflet_id = null;
                    }

                    const map = L.map('mapid').setView([item.latitude, item.longitude], 14);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                    }).addTo(map);

                    L.marker([item.latitude, item.longitude]).addTo(map)
                        .bindPopup(`${item.brand} ${item.model}`)
                        .openPopup();
                });
            }

            detailsModal.show();

        } else {
            $('#motorcycleDetails').html('<p class="text-danger">Error loading details</p>');
            detailsModal.show();
        }
    }, 'json').fail(function() {
        $('#motorcycleDetails').html('<p class="text-danger">Error loading details</p>');
        detailsModal.show();
    });
}

// =======================
// Search Models
// =======================
function searchModels() {
    const query = $('#searchModel').val().trim();
    if (query.length < 2) return;
    
    $('#modelList').html('<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>');
    
    $.get('../api/inventory_management.php', {
        action: 'search_inventory',
        query: query
    }, function(response) {
        if (response.success && response.data.length > 0) {
            const modelGroups = groupByModel(response.data);
            let html = '<h6>Search Results</h6>';
            
            Object.keys(modelGroups).forEach(model => {
                const items = modelGroups[model];
                html += `
                    <div class="card mb-2 model-item" data-model="${model}" data-id="${items[0].id}">
                        <div class="card-body">
                            <h6 class="card-title">${model}</h6>
                            <p class="card-text small">
                                ${items.length} available · 
                                ${items[0].color} · 
                                ${items[0].current_branch}
                            </p>
                        </div>
                    </div>
                `;
            });
            
            $('#modelList').html(html);
            
            $('.model-item').click(function() {
                const id = $(this).data('id');
                viewMotorcycleDetails(id);
            });
        } else {
            $('#modelList').html('<p class="text-muted">No matching models found</p>');
            $('#branchInfo').html('<h6>Search Results</h6>');
        }
    }, 'json');
}

function viewMotorcycleDetails(id) {
    // Show loading state in modal
    $('#detailsModal .modal-body').html('<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>');
    
    $.get('../api/inventory_management.php', {
        action: 'get_motorcycle',
        id: id
    }, function(response) {
        if (response.success) {
            const data = response.data;
            
            // Simple modal content without map
            $('#detailsModal .modal-body').html(`
                  <p><strong>Invoice Number/MT:</strong> ${data.invoice_number || 'N/A'}</p> 
                <h5>${data.brand} ${data.model}</h5>
                <p><strong>Color:</strong> ${data.color}</p>
                <p><strong>Branch:</strong> ${data.current_branch}</p>
                <p><strong>Engine No:</strong> ${data.engine_number}</p>
                <p><strong>Frame No:</strong> ${data.frame_number}</p>
                <p><strong>Status:</strong> ${data.status}</p>
                <p><strong>Date Delivered:</strong> ${data.date_delivered || 'N/A'}</p>
            `);
            
            // Show the modal
            $('#detailsModal').modal('show');
            
        } else {
            $('#detailsModal .modal-body').html('<p class="text-danger">Error loading details</p>');
            $('#detailsModal').modal('show');
        }
    }, 'json');
}

// =======================
// Monthly Inventory Report
// =======================
function showMonthlyInventoryOptions() {
    // Populate branches dropdown if not already done
    if ($('#selectedBranch option').length <= 1) {
        populateBranchesDropdown();
    }
    
    // Set current month as default
    const now = new Date();
    const currentMonth = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');
    $('#selectedMonth').val(currentMonth);
    
    // Show the options modal
    $('#monthlyInventoryOptionsModal').modal('show');
}


function toggleReportOptions() {
    const reportType = $('#reportPeriod').val();
    if (reportType === 'month') {
        $('#monthSelection').removeClass('d-none');
        $('#branchSelection').addClass('d-none');
    } else {
        $('#monthSelection').addClass('d-none');
        $('#branchSelection').removeClass('d-none');
    }
}
function populateBranchesDropdown() {
    const branches = [
        'HEADOFFICE', 'RXS-S', 'RXS-H', 'ANT-1', 'ANT-2', 'SDH', 'SDS', 
        'JAR-1', 'JAR-2', 'SKM', 'SKS', 'ALTA', 'EMAP', 'CUL', 'BAC', 
        'PAS-1', 'PAS-2', 'BAL', 'GUIM', 'PEMDI', 'EEM', 'AJU', 'BAIL', 
        '3SMB', '3SMIN', 'MAN', 'K-RIDERS', 'IBAJAY', 'NUMANCIA', 'CEBU'
    ];
    
    const $dropdown = $('#selectedBranch');
    branches.forEach(branch => {
        $dropdown.append(`<option value="${branch}">${branch}</option>`);
    });
}


function generateMonthlyInventoryReport() {
    const month = $('#selectedMonth').val();
    const branch = $('#selectedBranch').val();
    
    if (!month) {
        showErrorModal('Please select a month');
        return;
    }
    
    $('#monthlyInventoryOptionsModal').modal('hide');
    $('#monthlyReportContent').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>');
    
    $.ajax({
        url: '../api/inventory_management.php',
        method: 'GET',
        data: {
            action: 'get_monthly_inventory',
            month: month,
            branch: branch
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Store the data globally for PDF export
                currentReportData = response.data;
                currentReportMonth = response.month;
                currentReportBranch = response.branch;
                
                // Show in modal if needed
                renderMonthlyInventoryReport(response.data, response.month, response.branch);
                $('#monthlyInventoryReportModal').modal('show');
            } else {
                showErrorModal(response.message || 'Error generating report');
            }
        },
        error: function(xhr, status, error) {
            showErrorModal('Error generating report: ' + error);
        }
    });
}

function renderMonthlyInventoryReport(data, month, branch) {
    const [year, monthNum] = month.split('-');
    const monthName = new Date(year, monthNum - 1, 1).toLocaleString('default', { month: 'long' });
    const branchName = branch === 'all' ? 'All Branches' : branch;

    data.sort((a, b) => a.model.localeCompare(b.model));
    
    let totalIn = 0;
    let totalOut = 0;
    data.forEach(item => {
        totalIn += item.in_qty;
        totalOut += item.out_qty;
    });
    const endingBalance = totalIn - totalOut;

    let html = `
        <div class="report-header text-center mb-4">
            <div class="d-flex align-items-center justify-content-center mb-2">
                <div style="width: 40px; height: 2px; background: #000f71; margin-right: 15px;"></div>
                <h4 class="mb-0" style="color: #000f71; font-weight: 600; letter-spacing: 0.5px;">SOLID MOTORCYCLE DISTRIBUTORS, INC.</h4>
                <div style="width: 40px; height: 2px; background: #000f71; margin-left: 15px;"></div>
            </div>
            <h5 class="mb-2" style="color: #495057; font-weight: 500;">MONTHLY INVENTORY REPORT</h5>
            <h6 class="mb-2 text-muted" style="font-weight: 400;">${monthName} ${year}</h6>
            ${branch !== 'all' ? `<p class="mb-1"><span style="color: #6c757d;">Branch:</span> <span style="color: #000f71; font-weight: 500;">${branchName}</span></p>` : ''}
            <p class="text-muted small mb-0" style="font-size: 0.85rem;">Generated on ${new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</p>
        </div>
        
        <div class="row">
            <div class="col-md-8">
                <div class="table-container" style="border: 1px solid #e9ecef; border-radius: 6px; max-height: 60vh; overflow-y: auto;">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr style="background-color: #f8f9fa; border-bottom: 2px solid #dee2e6; position: sticky; top: 0; z-index: 10;">
                                <th class="text-center py-3" style="font-weight: 600; color: #495057; width: 60px;">QTY</th>
                                <th class="py-3" style="font-weight: 600; color: #495057;">MODEL</th>
                                <th class="py-3" style="font-weight: 600; color: #495057;">COLOR</th>
                                <th class="py-3" style="font-weight: 600; color: #495057;">BRAND</th>
                                <th class="py-3" style="font-weight: 600; color: #495057;">ENGINE NUMBER</th>
                                <th class="py-3" style="font-weight: 600; color: #495057;">FRAME NUMBER</th>
                            </tr>
                        </thead>
                        <tbody>
    `;

    if (data.length === 0) {
        html += `
            <tr>
                <td colspan="6" class="text-center py-5 text-muted" style="font-style: italic;">No inventory data found for this period</td>
            </tr>
        `;
    } else {
        data.forEach((item, index) => {
            const rowClass = index % 2 === 0 ? 'bg-white' : 'bg-light';
            html += `
                <tr class="${rowClass}">
                    <td class="text-center py-2" style="border-right: 1px solid #e9ecef;">1</td>
                    <td class="py-2" style="border-right: 1px solid #e9ecef;">${escapeHtml(item.model)}</td>
                    <td class="py-2" style="border-right: 1px solid #e9ecef;">${escapeHtml(item.color)}</td>
                    <td class="py-2" style="border-right: 1px solid #e9ecef;">${escapeHtml(item.brand)}</td>
                    <td class="py-2">${escapeHtml(item.engine_number)}</td>
                    <td class="py-2">${escapeHtml(item.frame_number)}</td>
                </tr>
            `;
        });
    }

    html += `
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="summary-section" style="position: sticky; top: 20px;">
                    <div class="card border-0 shadow-sm mb-4" style="border-radius: 8px;">
                        <div class="card-header bg-transparent border-0 pt-4 pb-3">
                            <h6 class="card-title text-center mb-0" style="color: #000f71; font-weight: 600; letter-spacing: 0.5px;">INVENTORY SUMMARY</h6>
                        </div>
                        <div class="card-body px-4 pb-4 pt-0">
                            <div class="summary-item d-flex justify-content-between align-items-center mb-3 pb-3" style="border-bottom: 1px solid #f1f3f4;">
                                <div>
                                    <div class="fw-semibold" style="color: #495057;">IN</div>
                                    <small class="text-muted" style="font-size: 0.8rem;">Inventory added during period</small>
                                </div>
                                <span class="fs-5 fw-bold" style="color: #28a745;">${totalIn}</span>
                            </div>
                            
                            <div class="summary-item d-flex justify-content-between align-items-center mb-3 pb-3" style="border-bottom: 1px solid #f1f3f4;">
                                <div>
                                    <div class="fw-semibold" style="color: #495057;">OUT</div>
                                    <small class="text-muted" style="font-size: 0.8rem;">Inventory transferred out</small>
                                </div>
                                <span class="fs-5 fw-bold" style="color: #dc3545;">${totalOut}</span>
                            </div>
                            
                            <div class="summary-item d-flex justify-content-between align-items-center pt-2">
                                <div>
                                    <div class="fw-bold" style="color: #000f71;">ENDING BALANCE</div>
                                    <small class="text-muted" style="font-size: 0.8rem;">Remaining inventory</small>
                                </div>
                                <span class="fs-4 fw-bold" style="color: #000f71;">${endingBalance}</span>
                            </div>
                        </div>
                    </div>
                    
                   
                </div>
            </div>
        </div>
        
        <style>
            .table-container {
                overflow: hidden;
            }
            .table th {
                font-weight: 600;
                font-size: 0.9rem;
            }
            .table td {
                font-size: 0.9rem;
                color: #495057;
            }
            .card {
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.04);
            }
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }
            
            /* Modal scroll fix */
            .modal-body {
                max-height: calc(100vh - 200px);
                overflow-y: auto;
            }
            
            /* Table header sticky fix */
            .table-container thead th {
                position: sticky;
                top: 0;
                background-color: #f8f9fa;
                z-index: 10;
            }
        </style>
    `;

    $('#monthlyReportContent').html(html);
    
    // Add CSS to make the modal scrollable
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            #monthlyInventoryReportModal .modal-body {
                max-height: calc(100vh - 200px);
                overflow-y: auto;
            }
            #monthlyInventoryReportModal .modal-dialog {
                max-width: 95%;
                height: calc(100vh - 100px);
            }
            #monthlyInventoryReportModal .modal-content {
                height: 100%;
            }
        `)
        .appendTo('head');

}

function generateMonthlyReportPDF() {
    // Check if we have report data available
    if (!currentReportData || !currentReportMonth) {
        showErrorModal('Please generate a report first before exporting to PDF');
        return;
    }
    
    const [year, monthNum] = currentReportMonth.split('-');
    const monthName = new Date(year, monthNum - 1, 1).toLocaleString('default', { month: 'long' });
    const branchName = currentReportBranch === 'all' ? 'All Branches' : currentReportBranch;

    // Calculate totals
    let totalIn = 0;
    let totalOut = 0;
    currentReportData.forEach(item => {
        totalIn += item.in_qty;
        totalOut += item.out_qty;
    });
    const endingBalance = totalIn - totalOut;

    // Build table rows
    const rowsHtml = currentReportData.map((item, index) => {
        const rowClass = index % 2 === 0 ? 'bg-white' : 'bg-light';
        return `
            <tr class="${rowClass}">
                <td style="text-align:center; border: 1px solid #e9ecef; padding: 8px;">1</td>
                <td style="border: 1px solid #e9ecef; padding: 8px;">${escapeHtml(item.model)}</td>
                <td style="border: 1px solid #e9ecef; padding: 8px;">${escapeHtml(item.color)}</td>
                <td style="border: 1px solid #e9ecef; padding: 8px;">${escapeHtml(item.brand)}</td>
                <td style="border: 1px solid #e9ecef; padding: 8px;">${escapeHtml(item.engine_number)}</td>
                <td style="border: 1px solid #e9ecef; padding: 8px;">${escapeHtml(item.frame_number)}</td>
            </tr>
        `;
    }).join('');

    // Full HTML content
    const html = `
        <div style="font-family: Arial, sans-serif; padding: 20px;">
            <div style="text-align: center; margin-bottom: 30px;">
                <div style="display: flex; align-items: center; justify-content: center; margin-bottom: 15px;">
                    <div style="width: 40px; height: 2px; background: #000f71; margin-right: 15px;"></div>
                    <h4 style="margin: 0; color: #000f71; font-weight: 600; letter-spacing: 0.5px;">SOLID MOTORCYCLE DISTRIBUTORS, INC.</h4>
                    <div style="width: 40px; height: 2px; background: #000f71; margin-left: 15px;"></div>
                </div>
                <h5 style="margin: 10px 0; color: #495057; font-weight: 500;">MONTHLY INVENTORY REPORT</h5>
                <h6 style="margin: 5px 0; color: #6c757d; font-weight: 400;">${monthName} ${year}</h6>
                ${currentReportBranch !== 'all' ? `<p style="margin: 5px 0;"><span style="color: #6c757d;">Branch:</span> <span style="color: #000f71; font-weight: 500;">${branchName}</span></p>` : ''}
                <p style="color: #6c757d; font-size: 12px; margin: 5px 0;">Generated on ${new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</p>
            </div>
            
            <div style="border: 1px solid #e9ecef; border-radius: 6px; margin-bottom: 20px;">
                <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
                    <thead>
                        <tr style="background-color: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                            <th style="text-align: center; padding: 12px; font-weight: 600; color: #495057; width: 60px;">QTY</th>
                            <th style="padding: 12px; font-weight: 600; color: #495057;">MODEL</th>
                            <th style="padding: 12px; font-weight: 600; color: #495057;">COLOR</th>
                            <th style="padding: 12px; font-weight: 600; color: #495057;">BRAND</th>
                            <th style="padding: 12px; font-weight: 600; color: #495057;">ENGINE NUMBER</th>
                            <th style="padding: 12px; font-weight: 600; color: #495057;">FRAME NUMBER</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${currentReportData.length === 0 ? `
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 30px; color: #6c757d; font-style: italic;">No inventory data found for this period</td>
                            </tr>
                        ` : rowsHtml}
                    </tbody>
                </table>
            </div>
            
            <div style="display: flex; justify-content: space-around; margin-top: 30px;">
                <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px; width: 30%;">
                    <div style="font-weight: 600; color: #495057; margin-bottom: 5px;">IN</div>
                    <div style="font-size: 24px; font-weight: bold; color: #28a745;">${totalIn}</div>
                    <div style="font-size: 11px; color: #6c757d;">Inventory added</div>
                </div>
                
                <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px; width: 30%;">
                    <div style="font-weight: 600; color: #495057; margin-bottom: 5px;">OUT</div>
                    <div style="font-size: 24px; font-weight: bold; color: #dc3545;">${totalOut}</div>
                    <div style="font-size: 11px; color: #6c757d;">Inventory transferred</div>
                </div>
                
                <div style="text-align: center; padding: 15px; background: #000f71; border-radius: 8px; width: 30%;">
                    <div style="font-weight: 600; color: white; margin-bottom: 5px;">ENDING BALANCE</div>
                    <div style="font-size: 24px; font-weight: bold; color: white;">${endingBalance}</div>
                    <div style="font-size: 11px; color: rgba(255,255,255,0.8);">Remaining inventory</div>
                </div>
            </div>
            
            
        </div>
    `;

    const container = document.createElement('div');
    container.innerHTML = html;

    const opt = {
        margin: 0.5,
        filename: `Monthly_Inventory_Report_${currentReportMonth}_${currentReportBranch}.pdf`,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
    };

    html2pdf().set(opt).from(container).save();
}


function exportMonthlyReportToPDF() {
    const reportEl = document.getElementById('monthlyReportPrintContainer');

    if (!reportEl || !reportEl.innerHTML.trim()) {
        alert('No report content available to export.');
        return;
    }

    // Temporarily make it visible for rendering
    reportEl.style.display = 'block';

    const opt = {
        margin:       0.5,
        filename:     `Monthly_Inventory_Report_${new Date().toISOString().slice(0,10)}.pdf`,
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true },
        jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
    };

    html2pdf().set(opt).from(reportEl).save().then(() => {
        // Hide it again after exporting
        reportEl.style.display = 'none';
    });
}

function exportMonthlyReport() {
    let csvContent = "data:text/csv;charset=utf-8,";
    
    // Get headers
    const headers = [];
    $('#monthlyReportContent thead th').each(function() {
        headers.push($(this).text().trim());
    });
    csvContent += headers.join(',') + '\n';
    
    // Get data rows
    $('#monthlyReportContent tbody tr').each(function() {
        const row = [];
        $(this).find('td').each(function() {
            row.push($(this).text().trim());
        });
        csvContent += row.join(',') + '\n';
    });
    
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', $('#monthlyInventoryReportModalLabel').text().toLowerCase().replace(/ /g, '_') + '.csv');
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}


// =======================
// Helper Functions
// =======================
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function formatCurrency(amount) {
    if (!amount) return 'N/A';
    return '₱' + parseFloat(amount).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function capitalizeFirstLetter(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const stringText = String(text);
    return stringText
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function groupByModel(items) {
    return items.reduce((groups, item) => {
        const key = `${item.brand} ${item.model}`;
        if (!groups[key]) groups[key] = [];
        groups[key].push(item);
        return groups;
    }, {});
}

function getStatusClass(status) {
    switch(status) {
        case 'available': return 'bg-success';
        case 'sold': return 'bg-danger';
        case 'transferred': return 'bg-warning text-dark';
        default: return 'bg-secondary';
    }
}