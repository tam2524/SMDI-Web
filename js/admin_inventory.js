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
let modelCount = 0;

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
    
    addModelForm();
    
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
                    showSuccessModal(response.message || 'Transfers accepted successfully! Received on: ' + response.date_received);
                    $('#incomingTransfersModal').modal('hide');
                    
                    // Wait for the success modal to show, then reload the page
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                    
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

    $('#addModelBtn').click(function() {
        addModelForm();
    });

    $('#addMotorcycleForm').submit(function(e) { 
        e.preventDefault(); 
        addMotorcycle(); 
    });

    // Add event listener for delete button
    $(document).on('click', '.delete-btn', function() {
        const id = $(this).closest('tr').data('id');
        showConfirmationModal(
            'Are you sure you want to delete this motorcycle from inventory?',
            'Delete Motorcycle',
            function() { deleteMotorcycle(id); }
        );
    });

    // Add event listener for delete multiple motorcycles button
    $('#deleteSelectedBtn').click(deleteMultipleMotorcycles);
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
                        <div class="model-card d-flex justify-content-between align-items-center ${brandColor}" 
                             data-brand="${item.brand}" data-model="${item.model}" onclick="filterByModel('${item.brand}', '${item.model}')">
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

// Update the filterByModel function to handle the search correctly
function filterByModel(brand, model) {
    // Switch to the management tab
    $('#management-tab').tab('show');
    
    // Set the search input to just the model name (not brand + model)
    $('#searchInventory').val(model);
    currentInventoryQuery = model;
    currentInventoryPage = 1;
    
    // Load the filtered table
    loadInventoryTable(currentInventoryPage, currentInventorySort, currentInventoryQuery);
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
                            <button class="btn btn-outline-danger delete-btn">
                                <i class="bi bi-trash"></i>
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

    $('.delete-btn').click(function() {
        const id = $(this).closest('tr').data('id');
        showConfirmationModal(
            'Are you sure you want to delete this motorcycle from inventory?', 
            'Delete Motorcycle',
            function() { deleteMotorcycle(id); }
        );
    });
}

// =======================
// Delete Motorcycle
// =======================
function deleteMotorcycle(id) {
    $.ajax({
        url: '../api/inventory_management.php',
        method: 'POST',
        data: {
            action: 'delete_motorcycle',
            id: id
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                loadInventoryTable(currentInventoryPage, currentInventorySort, currentInventoryQuery);
                showSuccessModal('Motorcycle deleted successfully!');
            } else {
                showErrorModal(response.message || 'Error deleting motorcycle');
            }
        },
        error: function(xhr, status, error) {
            showErrorModal('Error deleting motorcycle: ' + error);
        }
    });
}
// =======================
// Delete Multiple Motorcycles
// =======================
function deleteMultipleMotorcycles() {
    const selectedIds = [];
    $('#inventoryTableBody input[type="checkbox"]:checked').each(function() {
        selectedIds.push($(this).val());
    });
    
    if (selectedIds.length === 0) {
        showErrorModal('Please select at least one motorcycle to delete');
        return;
    }
    
    showConfirmationModal(
        'Are you sure you want to delete these motorcycles from inventory?',
        'Delete Motorcycles',
        function() { deleteMotorcycles(selectedIds); }
    );
}


// Delete Motorcycles
function deleteMotorcycles(ids) {
    $.ajax({
        url: '../api/inventory_management.php',
        method: 'POST',
        data: {
            action: 'delete_multiple_motorcycles',
            ids: ids.join(',')
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                loadInventoryTable(currentInventoryPage, currentInventorySort, currentInventoryQuery);
                showSuccessModal('Motorcycles deleted successfully!');
            } else {
                showErrorModal(response.message || 'Error deleting motorcycles');
            }
        },
        error: function(xhr, status, error) {
            showErrorModal('Error deleting motorcycles: ' + error);
        }
    });
}

// =======================
// Other Functions
// =======================
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
                $('#editInvoiceNumber').val(data.invoice_number || ''); 
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

// =======================
// Utility Functions
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

function showConfirmationModal(message, title, callback) {
    $('#confirmationMessage').text(message);
    $('#confirmationModalLabel').text(title);
    const modal = $('#confirmationModal');
    
    // Clear previous callback to avoid multiple bindings
    modal.off('click', '#confirmDeleteBtn');
    
    // Set up the callback for the confirm button
    modal.on('click', '#confirmDeleteBtn', function() {
        modal.modal('hide');
        if (typeof callback === 'function') callback();
    });
    
    modal.modal('show');
}