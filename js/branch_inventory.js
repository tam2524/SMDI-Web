let currentInventoryPage = 1;
let totalInventoryPages = 1;
let currentInventorySort = '';
let currentInventoryQuery = '';
let selectedRecordIds = [];
let hasShownIncomingTransfers = false; 
let shownTransferIds = [];
let lastCheckTime = new Date().toISOString(); 
let map;

$(document).ready(function() {
    shownTransferIds = [];

    setTimeout(() => {
        map = initMap(currentBranch);
    }, 100);

    loadInventoryTable();
    setupEventListeners();
    loadBranchInventory(currentBranch);

    setInterval(checkIncomingTransfers, 1000); // Check every 30 seconds
});

function setupEventListeners() {
    $('#searchModelBtn').click(searchModels);
    $('#searchModel').keypress(function(e) {
        if (e.which === 13) searchModels();
    });

    $('#transferSelectedBtn').click(transferSelectedMotorcycles);
$('#multipleTransferForm').submit(function(e) {
    e.preventDefault();
    performMultipleTransfers();
});

    
    $(document).on('change', '.motorcycle-checkbox', function() {
        updateSelectedRecords();
        toggleDeleteSelectedButton();
        
        const allChecked = $('.motorcycle-checkbox:checked').length === $('.motorcycle-checkbox').length;
        $('#selectAll').prop('checked', allChecked);
    });
    
    $('#addMotorcycleForm').submit(function(e) {
        e.preventDefault();
        addMotorcycle();
    });
    
    $('#editMotorcycleForm').submit(function(e) {
        e.preventDefault();
        updateMotorcycle();
    });
    
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
    
    $('#selectAll').click(function() {
        $('.motorcycle-checkbox').prop('checked', this.checked);
        updateSelectedRecords();
        toggleDeleteSelectedButton();
    });
    
    $('#deleteSelectedBtn').click(deleteSelectedMotorcycles);
    
    $('#confirmDeleteBtn').click(function() {
        const id = $('#confirmationModal').data('id');
        if (id) {
            deleteMotorcycle(id);
        } else {
            performBulkDelete(selectedRecordIds);
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
    $('.motorcycle-checkbox:checked').each(function() {
        selectedRecordIds.push($(this).val());
    });
}

function toggleDeleteSelectedButton() {
    const anyChecked = $('.motorcycle-checkbox:checked').length > 0;
    $('#deleteSelectedBtn').prop('disabled', !anyChecked);
    $('#transferSelectedBtn').prop('disabled', !anyChecked);
}

function showSuccessModal(message) {
    $('#successMessage').text(message);
    $('#successModal').modal('show');
    
    setTimeout(() => {
        $('#successModal').modal('hide');
    }, 2000);
}

function showErrorModal(message) {
    $('#errorMessage').text(message);
    $('#errorModal').modal('show');
    
    setTimeout(() => {
        $('#errorModal').modal('hide');
    }, 3000);
}

function showConfirmationModal(message, title, callback) {
    $('#confirmationMessage').text(message);
    $('#confirmationModalLabel').text(title); // Update modal title
    const modal = $('#confirmationModal');
    
    // Clear previous handlers
    modal.off('click', '#confirmActionBtn');
    
    // Add new handler
    modal.on('click', '#confirmActionBtn', function() {
        modal.modal('hide');
        if (typeof callback === 'function') {
            callback();
        }
    });
    
    modal.modal('show');
}

function checkIncomingTransfers() {
    $.ajax({
        url: '../api/inventory_management.php',
        method: 'GET',
        data: {
            action: 'get_incoming_transfers',
            last_check_time: lastCheckTime, // Send the last check time to the server
            current_branch: currentBranch // Add current branch to the request
        },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data.length > 0) {
                showIncomingTransfersModal(response.data);
                // Update the last check time to the current time after showing new transfers
                lastCheckTime = new Date().toISOString();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching incoming transfers:', error);
        }
    });
}

function showIncomingTransfersModal(transfers) {
    const tbody = $('#incomingTransfersBody');
    tbody.empty(); // Clear previous data
    transfers.forEach(transfer => {
        tbody.append(`
            <tr data-transfer-id="${transfer.transfer_id}">
                <td>${transfer.brand} ${transfer.model}</td>
                <td>${transfer.engine_number}</td>
                <td>${transfer.frame_number}</td>
                <td>${transfer.color}</td>
                <td>${transfer.transfer_date}</td>
                <td>${transfer.from_branch}</td>
            </tr>
        `);
    });
    $('#incomingTransfersModal').modal('show');
}
function transferSelectedMotorcycles() {
    const selectedIds = [];
    const selectedModels = [];
    
    $('.motorcycle-checkbox:checked').each(function() {
        const id = $(this).val();
        const row = $(this).closest('tr');
        const model = row.find('td:eq(3)').text(); // Model is in 4th column (0-based index 3)
        const engineNo = row.find('td:eq(4)').text();
        
        selectedIds.push(id);
        selectedModels.push(`${model} (${engineNo})`);
    });
    
    if (selectedIds.length === 0) {
        showErrorModal('Please select at least one motorcycle to transfer');
        return;
    }
    
    // Get the common branch for all selected motorcycles
    let commonBranch = null;
    let branchesMatch = true;
    
    $('.motorcycle-checkbox:checked').each(function() {
        const row = $(this).closest('tr');
        const branch = row.find('td:eq(8)').text(); // Branch is in 9th column (0-based index 8)
        
        if (commonBranch === null) {
            commonBranch = branch;
        } else if (commonBranch !== branch) {
            branchesMatch = false;
            return false; // break out of loop
        }
    });
    
    if (!branchesMatch) {
        showErrorModal('Cannot transfer motorcycles from different branches at once');
        return;
    }
    
    // Populate the modal
    $('#multipleFromBranch').val(commonBranch);
    $('#multipleTransferDate').val(new Date().toISOString().split('T')[0]);
    $('#selectedCount').text(selectedIds.length);
    
    const $list = $('#selectedMotorcyclesList');
    $list.empty();
    selectedModels.forEach(model => {
        $list.append(`<div class="mb-1">${model}</div>`);
    });
    
    // Clear and set HEADOFFICE as the only option
    const $toBranch = $('#multipleToBranch');
    $toBranch.empty().html('<option value="HEADOFFICE" selected>HEADOFFICE</option>');
    
    // Store the selected IDs in the modal
    $('#multipleTransferModal').data('selectedIds', selectedIds).modal('show');
    
    // Disable the branch selection since we only have one option
    $toBranch.prop('disabled', true);
}

function performMultipleTransfers() {
    const selectedIds = $('#multipleTransferModal').data('selectedIds');
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
                
                // Clear checkboxes
                $('.motorcycle-checkbox').prop('checked', false);
                $('#selectAll').prop('checked', false);
                updateSelectedRecords();
                toggleDeleteSelectedButton();
            } else {
                showErrorModal(response.message || 'Error transferring motorcycles');
            }
        },
        error: function(xhr, status, error) {
            showErrorModal('Error transferring motorcycles: ' + error);
        }
    });
}


// Accept all transfers
$('#acceptAllTransfersBtn').click(function() {
    const transferIds = [];
    $('#incomingTransfersBody tr').each(function() {
        transferIds.push($(this).data('transfer-id'));
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
                showSuccessModal(response.message);
                $('#incomingTransfersModal').modal('hide');
                loadInventoryTable(); // Refresh inventory
            } else {
                showErrorModal(response.message);
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
                totalInventoryPages = response.pagination.totalPages || 1;
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
    
    paginationHtml += `
        <li class="page-item ${currentInventoryPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" id="prevPage">
                <i class="fas fa-chevron-left me-1"></i> Previous
            </a>
        </li>`;
    
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
    
    for (let i = startPage; i <= endPage; i++) {
        paginationHtml += `
            <li class="page-item ${currentInventoryPage === i ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>`;
    }
    
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
            const showReturnBtn = item.current_branch !== 'HEADOFFICE';
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
                           
                            ${showReturnBtn ? 
                              `<button class="btn btn-outline-warning return-btn" title="Return to Head Office">
                                  <i class="bi bi-house-door"></i>
                              </button>` : ''}
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
            function() {
                returnToHeadOffice(id);
            }
        );
    });
    
    $('.delete-btn').click(function() {
        const id = $(this).closest('tr').data('id');
        showConfirmationModal(
            'Are you sure you want to delete this motorcycle from inventory? This action cannot be undone.', 
            'Confirm Deletion',
            function() {
                deleteMotorcycle(id);
            }
        );
    });
}
function returnToHeadOffice(motorcycleId) {
    const currentBranch = $(`tr[data-id="${motorcycleId}"] td:nth-child(9)`).text().trim();
    
    const transferData = {
        action: 'transfer_motorcycle',
        motorcycle_id: motorcycleId,
        from_branch: currentBranch,
        to_branch: 'HEADOFFICE',
        transfer_date: new Date().toISOString().split('T')[0],
        notes: 'Returned to Head Office'
    };
    
    $.ajax({
        url: '../api/inventory_management.php',
        method: 'POST',
        data: transferData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showSuccessModal('Motorcycle returned to Head Office successfully!');
                loadInventoryTable(currentInventoryPage, currentInventorySort, currentInventoryQuery);
            } else {
                showErrorModal(response.message || 'Error returning motorcycle to Head Office');
            }
        },
        error: function(xhr, status, error) {
            showErrorModal('Error returning motorcycle to Head Office: ' + error);
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
                $('#editColor').val(data.color);
                $('#editLcp').val(data.lcp);
                $('#editCurrentBranch').val(data.current_branch).prop('disabled', true);
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
                
                loadInventoryTable(currentInventoryPage, currentInventorySort, currentInventoryQuery);
                loadBranchInventory(currentBranch);
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
                showSuccessModal('Motorcycle deleted successfully!');
                loadInventoryTable(currentInventoryPage, currentInventorySort, currentInventoryQuery);
            } else {
                showErrorModal(response.message || 'Error deleting motorcycle');
            }
        },
        error: function(xhr, status, error) {
            showErrorModal('Error deleting motorcycle: ' + error);
        }
    });
}

function deleteSelectedMotorcycles() {
    if (selectedRecordIds.length === 0) {
        showErrorModal('Please select at least one motorcycle to delete');
        return;
    }
    
    showConfirmationModal(`Are you sure you want to delete ${selectedRecordIds.length} selected motorcycles? This action cannot be undone.`, function() {
        performBulkDelete(selectedRecordIds);
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
            if (response.success) {
                showSuccessModal(response.message || 'Selected motorcycles deleted successfully!');
                loadInventoryTable(currentInventoryPage, currentInventorySort, currentInventoryQuery);
            } else {
                showErrorModal(response.message || 'Error deleting selected motorcycles');
            }
        },
        error: function(xhr, status, error) {
            showErrorModal('Error deleting selected motorcycles: ' + error);
        }
    });
}

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
    
    // Initialize the modal properly
    const detailsModal = new bootstrap.Modal(document.getElementById('detailsModal'));
    
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

            if (item.status === 'transferred' && item.transfer_history && item.transfer_history.length > 0) {
                detailsHTML += `
                    <hr>
                    <h6>Transfer History</h6>
                    <div class="transfer-history">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                item.transfer_history.forEach(transfer => {
                    detailsHTML += `
                        <tr>
                            <td>${transfer.transfer_date}</td>
                            <td>${transfer.from_branch}</td>
                            <td>${transfer.to_branch}</td>
                            <td>${transfer.notes || '-'}</td>
                        </tr>
                    `;
                });

                detailsHTML += `
                            </tbody>
                        </table>
                    </div>
                `;
            }

            $('#motorcycleDetails').html(detailsHTML);
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
            $('#branchInfo').html('<h6>Search Results</h6>');
            
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
                
                $('#detailBrand').text(data.brand);
                $('#detailModel').text(data.model);
                $('#detailColor').text(data.color);
                $('#detailDateDelivered').text(formatDate(data.date_delivered));
                $('#detailEngineNumber').text(data.engine_number);
                $('#detailFrameNumber').text(data.frame_number);
                $('#detailCurrentBranch').text(data.current_branch);
                $('#detailStatus').text(data.status.charAt(0).toUpperCase() + data.status.slice(1));
                $('#detailLcp').text(formatCurrency(data.lcp));
                
                const detailMap = L.map('detailMap').setView([11.5852, 122.7511], 10);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(detailMap);
                
                const branchCoordinates = {
                    'RXS-S': [11.581639063474135, 122.75283046163139],
                    'RXS-H': [11.591933174094493, 122.75177370058198],
                    'HEADOFFICE': [11.58156063320175, 122.75277786727027]
                };
                
                const coord = branchCoordinates[data.current_branch] || [11.5852, 122.7511];
                L.marker(coord).addTo(detailMap)
                    .bindPopup(`<b>${data.current_branch}</b>`)
                    .openPopup();
                
                if (data.transfer_history && data.transfer_history.length > 0) {
                    let historyHtml = '';
                    data.transfer_history.forEach(transfer => {
                        historyHtml += `
                            <tr>
                                <td>${formatDate(transfer.transfer_date)}</td>
                                <td>${transfer.from_branch}</td>
                                <td>${transfer.to_branch}</td>
                                <td>${transfer.notes || '-'}</td>
                            </tr>
                        `;
                    });
                    $('#transferHistoryBody').html(historyHtml);
                } else {
                    $('#transferHistoryBody').html('<tr><td colspan="4" class="text-center text-muted">No transfer history</td></tr>');
                }
                
                $('#viewDetailsModal').modal('show');
            } else {
                showErrorModal(response.message || 'Error loading motorcycle details');
            }
        },
        error: function(xhr, status, error) {
            showErrorModal('Error loading motorcycle details: ' + error);
        }
    });
}

function groupByModel(items) {
    return items.reduce((groups, item) => {
        const key = `${item.brand} ${item.model}`;
        if (!groups[key]) groups[key] = [];
        groups[key].push(item);
        return groups;
    }, {});
}

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

function getStatusClass(status) {
    switch(status) {
        case 'available': return 'bg-success';
        case 'sold': return 'bg-danger';
        case 'transferred': return 'bg-warning text-dark';
        default: return 'bg-secondary';
    }
}