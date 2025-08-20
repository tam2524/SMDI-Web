// Global variables for pagination and search
let currentInventoryPage = 1;
let totalInventoryPages = 1;
let currentInventorySort = '';
let currentInventoryQuery = '';
let selectedRecordIds = [];
let hasShownIncomingTransfers = false; 
let shownTransferIds = [];
let lastCheckTime = new Date().toISOString();
// Add these at the top with your other global variables
let currentReportData = null;
let currentReportMonth = null;
let currentReportBranch = null;
// Add these variables to your global variables section
let selectedMotorcycles = [];



$(document).ready(function() {
    shownTransferIds = [];
    // Initialize the page
    loadInventoryDashboard();
    loadInventoryTable();
    
    // Set up event listeners
    setupEventListeners();
    
    // Enable the transfer button
    $('#transferSelectedBtn').prop('disabled', false);
    
    $('.modal').on('hidden.bs.modal', function() {
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open');
    });

    setInterval(checkIncomingTransfers, 1000); 
});
function setupEventListeners() {

    $('#searchEngineBtn').click(searchMotorcyclesByEngine);
$('#engineSearch').keypress(function(e) {
    if (e.which == 13) {
        searchMotorcyclesByEngine();
        e.preventDefault();
    }
});
    $('#generateMonthlyInventory').click(showMonthlyInventoryOptions);
$('#reportPeriod').change(toggleReportOptions);
$('#generateReportBtn').click(generateMonthlyInventoryReport);
$('#exportMonthlyReportToPDF').click(generateMonthlyReportPDF);
$('#exportMonthlyReport').click(exportMonthlyReport);

$('#transferSelectedBtn').click(transferSelectedMotorcycles);
     // Add event listener for delete selected button
    $('#deleteSelectedBtn').click(deleteSelectedMotorcycles);
    
    // Add motorcycle form submission
    $('#addMotorcycleForm').submit(function(e) {
        e.preventDefault();
        addMotorcycle();
    });
    
    // Edit motorcycle form submission
    $('#editMotorcycleForm').submit(function(e) {
        e.preventDefault();
        updateMotorcycle();
    });
    
    // Transfer motorcycle form submission
    $('#transferMotorcycleForm').submit(function(e) {
        e.preventDefault();
        transferMotorcycle();
    });
    
    // Search dashboard
    $('#searchDashboardBtn').click(function() {
        loadInventoryDashboard($('#searchDashboard').val());
    });
    
    $('#searchDashboard').keypress(function(e) {
        if (e.which == 13) {
            loadInventoryDashboard($(this).val());
        }
    });
    
    // Search inventory
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
    
    // Select all checkbox
    $('#selectAll').click(function() {
        $('.motorcycle-checkbox').prop('checked', this.checked);
        updateSelectedRecords();
        toggleDeleteSelectedButton();
    });
    
    // Individual checkbox change
    $(document).on('change', '.motorcycle-checkbox', function() {
        updateSelectedRecords();
        toggleDeleteSelectedButton();
    });
    
    // Pagination click events
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
    
    // Sorting functionality
    $('.sortable-header').on('click', function() {
        const sortField = $(this).data('sort');
        if (currentInventorySort === sortField + '_asc') {
            currentInventorySort = sortField + '_desc';
        } else {
            currentInventorySort = sortField + '_asc';
        }
        loadInventoryTable(currentInventoryPage, currentInventorySort, currentInventoryQuery);
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
            action: 'search_inventory',
            query: searchTerm,
            field: 'engine_number'
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                displaySearchResults(response.data);
            } else {
                showErrorModal(response.message || 'Error searching motorcycles');
            }
        },
        error: function(xhr, status, error) {
            showErrorModal('Error searching motorcycles: ' + error);
        }
    });
}

// Add this function to display search results
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
        
        html += `
            <div class="transfer-search-result ${isSelected ? 'selected' : ''}" 
                 onclick="toggleMotorcycleSelection(${motorcycle.id}, '${motorcycle.engine_number}', '${motorcycle.brand}', '${motorcycle.model}', '${motorcycle.color}', '${motorcycle.current_branch}')">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="engine-number">${motorcycle.engine_number}</div>
                        <div class="model-info">${motorcycle.brand} ${motorcycle.model} - ${motorcycle.color}</div>
                        <div class="branch-info">
                            <i class="bi bi-geo-alt me-1"></i>${motorcycle.current_branch}
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm ${isSelected ? 'btn-danger' : 'btn-success'} select-btn ms-2"
                            onclick="event.stopPropagation(); toggleMotorcycleSelection(${motorcycle.id}, '${motorcycle.engine_number}', '${motorcycle.brand}', '${motorcycle.model}', '${motorcycle.color}', '${motorcycle.current_branch}')">
                        ${isSelected ? 'Remove' : 'Select'}
                    </button>
                </div>
            </div>
        `;
    });
    
    $resultsContainer.html(html);
}


// Add this function to handle selection/deselection
function toggleMotorcycleSelection(id, engineNumber, brand, model, color, currentBranch) {
    const index = selectedMotorcycles.findIndex(m => m.id === id);
    
    if (index === -1) {
        // Add to selection
        selectedMotorcycles.push({
            id: id,
            engine_number: engineNumber,
            brand: brand,
            model: model,
            color: color,
            current_branch: currentBranch
        });
    } else {
        // Remove from selection
        selectedMotorcycles.splice(index, 1);
    }
    
    updateSelectedMotorcyclesList();
    // Refresh search results to update button states
    searchMotorcyclesByEngine();
}

// Add this function to update the selected motorcycles list
function updateSelectedMotorcyclesList() {
    const $selectedList = $('#selectedMotorcyclesList');
    const $selectedCount = $('#selectedCount');
    
    $selectedCount.text(selectedMotorcycles.length);
    
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
        html += `
            <div class="selected-motorcycle-item">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center mb-1">
                            <span class="badge bg-primary me-2">${index + 1}</span>
                            <span class="fw-semibold text-primary">${motorcycle.engine_number}</span>
                        </div>
                        <div class="small text-muted mb-1">${motorcycle.brand} ${motorcycle.model} - ${motorcycle.color}</div>
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

// Add this function to remove a motorcycle from selection
function removeMotorcycleFromSelection(id) {
    const index = selectedMotorcycles.findIndex(m => m.id === id);
    if (index !== -1) {
        selectedMotorcycles.splice(index, 1);
        updateSelectedMotorcyclesList();
        // Refresh search results to update button states
        searchMotorcyclesByEngine();
    }
}
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
    
    const branches = ['RXS-S', 'RXS-H', 'ANT-1', 'ANT-2', 'SDH', 'SDS', 'JAR-1', 'JAR-2', 'SKM', 'SKS', 'ALTA', 'EMAP', 'CUL', 'BAC', 'PAS-1', 'PAS-2', 'BAL', 'GUIM', 'PEMDI', 'EEM', 'AJU', 'BAIL', 'MINDORO MB', 'MINDORO 3S', 'MANSALAY', 'K-RIDERS', 'IBAJAY', 'NUMANCIA', 'HEADOFFICE', 'CEBU', 'GT'];
    
    branches.forEach(branch => {
        if (branch !== currentBranch) {
            $toBranch.append(`<option value="${branch}">${branch}</option>`);
        }
    });
    
    // Show the modal
    $('#multipleTransferModal').modal('show');
}
$('#multipleTransferForm').submit(function(e) {
    e.preventDefault();
    performMultipleTransfers();
});
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
                showSuccessModal('Motorcycles transferred successfully!');
                loadInventoryTable(currentInventoryPage, currentInventorySort, currentInventoryQuery);
                
                // Clear selection
                selectedMotorcycles = [];
                updateSelectedMotorcyclesList();
                $('#engineSearch').val('');
                $('#searchResults').html('<div class="text-center text-muted py-3">Search for motorcycles using engine number</div>');
            } else {
                showErrorModal(response.message || 'Error transferring motorcycles');
            }
        },
        error: function(xhr, status, error) {
            showErrorModal('Error transferring motorcycles: ' + error);
        }
    });
}

// Add this to reset the selection when the modal is closed
$('#multipleTransferModal').on('hidden.bs.modal', function() {
    selectedMotorcycles = [];
    updateSelectedMotorcyclesList();
    $('#engineSearch').val('');
    $('#searchResults').html('<div class="text-center text-muted py-3">Search for motorcycles using engine number</div>');
});

function updateSelectedRecords() {
    selectedRecordIds = [];
    $('#inventoryTableBody input[name="recordCheckbox"]:checked').each(function() {
        selectedRecordIds.push($(this).val());
    });
}

function toggleDeleteSelectedButton() {
    const anyChecked = $('.motorcycle-checkbox:checked').length > 0;
    $('#deleteSelectedBtn').prop('disabled', !anyChecked);

}
function showSuccessModal(message) {
    $('.modal-backdrop').remove();
    $('#successModal').removeClass('show');
    $('#successModal').css('display', 'none');
    
    $('#successMessage').text(message);
    $('#successModal').modal('show');
    
    setTimeout(() => {
        $('#successModal').modal('hide');
        setTimeout(() => {
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open');
        }, 150);
    }, 2000);
}

function showErrorModal(message) {
    $('.modal-backdrop').remove();
    $('#errorModal').removeClass('show');
    $('#errorModal').css('display', 'none');
    
    $('#errorMessage').text(message);
    $('#errorModal').modal('show');
    
    setTimeout(() => {
        $('#errorModal').modal('hide');
        setTimeout(() => {
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open');
        }, 150);
    }, 3000);
}

function showWarningModal(message) {
    $('.modal-backdrop').remove();
    $('#warningModal').removeClass('show');
    $('#warningModal').css('display', 'none');
    
    $('#warningMessage').text(message);
    $('#warningModal').modal('show');
    
    setTimeout(() => {
        $('#warningModal').modal('hide');
        setTimeout(() => {
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open');
        }, 150);
    }, 3000);
}

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
                    <td><input type="checkbox" class="motorcycle-checkbox" name="recordCheckbox" value="${item.id}"></td>
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
    toggleDeleteSelectedButton();
}

function getStatusBadgeClass(status) {
    switch(status) {
        case 'available': return 'bg-success';
        case 'sold': return 'bg-danger';
        case 'transferred': return 'bg-warning text-dark';
        default: return 'bg-secondary';
    }
}


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
                loadInventoryTable(); // Refresh inventory
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

// Reset the flag when modal is closed
$(document).on('hidden.bs.modal', '#incomingTransfersModal', function () {
    hasShownIncomingTransfers = false;
});

$(document).on('hidden.bs.modal', '#incomingTransfersModal', function () {
    hasShownIncomingTransfers = false; 
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

// Clear selection button
$('#clearSelectionBtn').click(function() {
    selectedMotorcycles = [];
    updateSelectedMotorcyclesList();
    $('#searchResults .transfer-search-result').removeClass('selected');
    $('#searchResults .select-btn')
        .removeClass('btn-danger')
        .addClass('btn-success')
        .text('Select');
});


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
                
                const branches = ['RXS-S', 'RXS-H', 'ANT-1', 'ANT-2', 'SDH', 'SDS', 'JAR-1', 'JAR-2', 'SKM', 'SKS', 'ALTA', 'EMAP', 'CUL', 'BAC', 'PAS-1', 'PAS-2', 'BAL', 'GUIM', 'PEMDI', 'EEM', 'AJU', 'BAIL', 'MINDORO MB', 'MINDORO 3S', 'MANSALAY', 'K-RIDERS', 'IBAJAY', 'NUMANCIA', 'HEADOFFICE', 'CEBU', 'GT'];
                
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

function addMotorcycle() {
    const formData = {
        action: 'add_motorcycle',
        date_delivered: $('#dateDelivered').val(),
        brand: $('#brand').val(),
        model: $('#model').val(),
        engine_number: $('#engineNumber').val(),
        frame_number: $('#frameNumber').val(),
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
                $('#addMotorcycleModal').modal('hide');
                $('#addMotorcycleForm')[0].reset();
                showSuccessModal('Motorcycle added successfully!');
                
                // Reload data after success
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

function deleteSelectedMotorcycles() {
    const selectedIds = [];
    $('.motorcycle-checkbox:checked').each(function() {
        selectedIds.push($(this).val());
    });
    
    if (selectedIds.length === 0) {
        showErrorModal('Please select at least one motorcycle to delete');
        return;
    }
    
    // Show confirmation modal
    $('#confirmationModal').modal('show');
    $('#confirmDeleteBtn').off('click').on('click', function() {
        performBulkDelete(selectedIds);
    });
}

function performBulkDelete(ids) {
    $.ajax({
        url: '../api/inventory_management.php',
        method: 'POST',
        data: {
            action: 'delete_multiple_motorcycles',
            ids: ids
        },
        dataType: 'json',
        success: function(response) {
            $('#confirmationModal').modal('hide');
            if (response.success) {
                showSuccessModal(response.message || 'Selected motorcycles deleted successfully!');
                loadInventoryTable(currentInventoryPage, currentInventorySort, currentInventoryQuery);
            } else {
                showErrorModal(response.message || 'Error deleting selected motorcycles');
            }
        },
        error: function(xhr, status, error) {
            $('#confirmationModal').modal('hide');
            showErrorModal('Error deleting selected motorcycles: ' + error);
        }
    });
}
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



// Helper functions
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString();
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

function setupTableActionButtons() {
    $('.edit-btn').click(function() {
        const id = $(this).closest('tr').data('id');
        loadMotorcycleForEdit(id);
    });
    
    $('.transfer-btn').click(function() {
        const id = $(this).closest('tr').data('id');
        loadMotorcycleForTransfer(id);
    });
    
    $('.delete-btn').click(function() {
        const id = $(this).closest('tr').data('id');
        $('#confirmationModal').data('id', id).modal('show');
    });
}

// Safe HTML escaping function
function escapeHtml(text) {
    if (text === null || text === undefined) {
        return '';
    }
    
    // Convert to string if it's not already
    const stringText = String(text);
    
    return stringText
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}