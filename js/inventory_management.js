// Inventory Management System with Pagination and Search
$(document).ready(function() {
    // Initialize the page
    loadInventoryDashboard();
    loadInventoryTable();
    
    // Set up event listeners
    setupEventListeners();
    
    // Add event listener for delete selected button
    $('#deleteSelectedBtn').click(deleteSelectedMotorcycles);
    
    $('.modal').on('hidden.bs.modal', function() {
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open');
    });
});

// Global variables for pagination and search
let currentInventoryPage = 1;
let totalInventoryPages = 1;
let currentInventorySort = '';
let currentInventoryQuery = '';
let selectedRecordIds = [];

function setupEventListeners() {
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

function loadInventoryDashboard(searchTerm = '') {
    $('#inventoryCards').html('<div class="col-12 text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
    
    $.ajax({
        url: '../api/inventory_management.php',
        method: 'GET',
        data: {
            action: 'get_inventory_dashboard',
            search: searchTerm
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                renderInventoryCards(response.data);
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
        data.forEach(item => {
            html += `
                <div class="col-xl-1 col-lg-2 col-md-3 col-sm-4 col-6 model-card-container">
                    <div class="model-card d-flex justify-content-between align-items-center">
                        <div class="model-name" title="${item.model}">${item.model}</div>
                        <div class="quantity-badge">${item.total_quantity}</div>
                    </div>
                </div>
            `;
        });
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
                    <td><span class="badge ${getStatusBadgeClass(item.status)}">${capitalizeFirstLetter(item.status)}</span></td>
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