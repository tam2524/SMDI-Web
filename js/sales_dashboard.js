// Define the models for each brand
const brandModels = {
    "Suzuki": ["GSX-250RL/FRLX", "GSX-150", "BIGBIKE", "GSX150FRF NEW", "GSX-S150", "UX110NER", "UB125", "AVENIS", "FU150", "FU150-FI", "FW110D", "FW110SD/SC", "DS250RL", "FJ110 LB-2", "FW110D(SMASH FI)", "FJ110LX", "UB125LNM(NEW)", "UK110", "UX110", "UK125", "GD110"],
    "Honda": ["GIORNO+", "CCG 125", "CFT125MRCS", "AFB110MDJ", "AFS110MDJ", "AFB110MDH", "CFT125MSJ", "AFS110MCDE", "MRCP", "DIO", "MSM", "MRP", "MRS", "CFT125MRCJ", "MSP", "MSS", "AFP110DFP", "MRCP", "AFP110DFR", "ZN125", "PCX160NEW", "PCX160", "AFB110MSJ", "AFP110SFR", "AFP110SFP", "CBR650", "CB500", "CB650R", "GL150R", "CBR500", "AIRBLADE 150", "AIRBLADE160", "ADV160", "CBR150RMIV/RAP", "BEAT-CSFN/FR/R3/FS/3", "CB150X", "WINNER X", "CRF-150", "CRF300", "CMX500", "XR150", "ACB160", "ACB125"],
    "Yamaha": ["MIO SPORTY", "MIOI125", "MIO GEAR", "SNIPER", "MIO GRAVIS", "YTX", "YZF R3", "FAZZIO", "XSR", "VEGA", "AEROX", "XTZ", "NMAX", "PG-1 BRN1", "MT-15", "FZ", "R15M BNE1/2", "XMAX", "WR155", "SEROW"],
    "Kawasaki": ["CT100 A", "CT100B", "CT125", "CA100AA NEW", "BC175H/MS", "BC175J/NN/SN", "BC175 III ELECT.", "BC175 III KICK", "BRUSKY", "NS125", "ELIMINATOR SE", "CT100B", "NINJA ZX 4RR", "Z900 SE", "KLX140", "KLX150", "CT150BA", "ROUSER 200", "W800", "VERYS 650", "KLX232", "NINJA ZX-10R", "Z900 SE"]
};

let selectedRecordIds = [];
let saleIdToDelete = null;
let currentPage = 1;
let currentSort = '';
let totalPages = 1;

$(document).ready(function() {
    // Initialize models dropdown on page load
    updateModelsDropdown($('#brand').val(), $('#model'));
    
    // Initialize modals and event handlers
    initSalesTable();
    initQuotaManagement();
    initSummaryReport();
    initModals();
    
    // Initial load of sales
    loadSales();
});

// ==================== SALES TABLE FUNCTIONS ====================
function initSalesTable() {
    // Handle brand change to update models dropdown
    $('#brand').change(function() {
        updateModelsDropdown($(this).val(), $('#model'));
    });

    // Handle edit brand change
    $('#editBrand').change(function() {
        updateModelsDropdown($(this).val(), $('#editModel'));
    });

    // Search input event
    $('#searchInput').on('input', function() {
        const query = $(this).val();
        currentPage = 1; // Reset to first page when searching
        loadSales(query);
    });

    // Pagination click events - updated version
    $(document).on('click', '.page-link', function(e) {
        e.preventDefault();
        if ($(this).parent().hasClass('disabled')) return;
        
        const oldPage = currentPage;
        
        if ($(this).attr('id') === 'prevPage') {
            currentPage = Math.max(1, currentPage - 1);
        } else if ($(this).attr('id') === 'nextPage') {
            currentPage = Math.min(totalPages, currentPage + 1);
        } else {
            currentPage = parseInt($(this).data('page'));
        }
        
        // Only load if page actually changed
        if (currentPage !== oldPage) {
            loadSales($('#searchInput').val(), currentPage, currentSort);
        }
    });

    // Sorting functionality - ensure this updates currentSort
    $('.dropdown-item').on('click', function(e) {
        e.preventDefault();
        currentSort = $(this).data('sort');
        currentPage = 1;
        loadSales($('#searchInput').val(), currentPage, currentSort);
    });

    // Handle checkbox changes
    $('#salesTableBody').on('change', 'input[name="recordCheckbox"]', function() {
        updateSelectedRecords();
    });

    // Handle select all checkbox
    $('#selectAll').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('#salesTableBody input[name="recordCheckbox"]').prop('checked', isChecked);
        updateSelectedRecords();
    });
}


$('#uploadSalesDataForm').submit(function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'upload_sales_data');
        $.ajax({
            url: '../api/sales_data_management.php',
            method: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    $('#uploadSalesDataModal').modal('hide');
                    loadSales(); // Reload sales data
                } else {
                    alert(response.message);
                }
            },
            error: function(xhr, status, error) {
                alert('Error uploading sales data: ' + error);
            }
        });
    });
function loadSales(query = '', page = 1, sort = '') {
    // Show loading state
    $('#salesTableBody').html('<tr><td colspan="7" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>');
    
    $.ajax({
        url: '../api/sales_data_management.php',
        method: 'GET',
        data: { 
            action: 'get_sales',
            query: query,
            page: page,
            sort: sort
        },
        success: function(response) {
            if (response.success) {
                totalPages = response.totalPages || 1;
                currentPage = Math.min(currentPage, totalPages); // Ensure currentPage is valid
                
                if (response.data && response.data.length > 0) {
                    $('#salesTableBody').html(generateTableRows(response.data));
                    updatePaginationControls(totalPages);
                    $('#pageInfo').text(`Page ${currentPage} of ${totalPages}`);
                } else {
                    $('#salesTableBody').html('<tr><td colspan="7" class="text-center text-muted py-4">No records found</td></tr>');
                }
            } else {
                showErrorModal(response.message || 'Failed to load sales data');
                $('#salesTableBody').html('<tr><td colspan="7" class="text-center text-danger">Error loading data</td></tr>');
            }
        },
        error: function(xhr, status, error) {
            showErrorModal('Error loading sales: ' + error);
            $('#salesTableBody').html('<tr><td colspan="7" class="text-center text-danger">Error loading data</td></tr>');
        }
    });
}
function generateTableRows(sales) {
    let rows = '';
    sales.forEach(sale => {
        rows += `
            <tr data-id="${sale.id}">
                <td><input type="checkbox" name="recordCheckbox" value="${sale.id}"></td>
                <td>${new Date(sale.sales_date).toLocaleDateString()}</td>
                <td>${sale.branch}</td>
                <td>${sale.brand}</td>
                <td>${sale.model}</td>
                <td>${sale.qty}</td>
                <td class="no-print">
                    <div class="btn-group btn-group-sm" role="group">
                        <button class="btn btn-outline-primary edit-button">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-outline-danger delete-button">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });
    return rows;
}

function updatePaginationControls(totalPages) {
    let paginationHtml = '';
    const maxVisiblePages = 5;
    let startPage, endPage;
    
    if (totalPages <= maxVisiblePages) {
        startPage = 1;
        endPage = totalPages;
    } else {
        const half = Math.floor(maxVisiblePages / 2);
        if (currentPage <= half + 1) {
            startPage = 1;
            endPage = maxVisiblePages;
        } else if (currentPage >= totalPages - half) {
            startPage = totalPages - maxVisiblePages + 1;
            endPage = totalPages;
        } else {
            startPage = currentPage - half;
            endPage = currentPage + half;
        }
    }
    

    
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
            <li class="page-item ${currentPage === i ? 'active' : ''}">
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
    
    
    
    $('#paginationControls').html(paginationHtml);
}

function updateModelsDropdown(brand, $dropdown, defaultValue = '') {
    $dropdown.empty();
    if (brand && brandModels[brand]) {
        brandModels[brand].forEach(model => {
            $dropdown.append($('<option>', {
                value: model,
                text: model
            }));
        });
        
        if (defaultValue) {
            $dropdown.val(defaultValue);
        }
    }
}
function updateSelectedRecords() {
    selectedRecordIds = [];
    $('#salesTableBody input[name="recordCheckbox"]:checked').each(function() {
        selectedRecordIds.push($(this).val());
    });
}

// Handle CSV file upload with date
$('#uploadSalesDataForm').on('submit', function(e) {
    e.preventDefault();
    
    // Show loading spinner
    $('#loadingSpinner').show();
    
    let formData = new FormData();
    formData.append('file', $('#file')[0].files[0]);
    formData.append('sales_date', $('#salesDate').val());
    formData.append('action', 'upload_sales_data');
    
    $.ajax({
        url: '../api/sales_data_management.php',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function(response) {
            $('#loadingSpinner').hide();
            
            if (response.success) {
                showSuccessModal(response.message);
                loadSales(currentPage, currentSort, currentQuery);
                $('#uploadSalesDataModal').modal('hide');
                $('#uploadSalesDataForm')[0].reset();
            } else {
                showWarningModal(response.message || 'Error uploading file');
            }
        },
        error: function(xhr, status, error) {
            $('#loadingSpinner').hide();
            showWarningModal('Error: ' + error);
        }
    });
});

// ==================== QUOTA MANAGEMENT FUNCTIONS ====================
function initQuotaManagement() {
    // Initialize quota modal when shown
    $('#salesQuotaModal').on('shown.bs.modal', function() {
        populateBranchDropdown();
        loadQuotas();
        resetQuotaForm();
    });

    // Handle add quota button click
    $('#addQuotaBtn').on('click', function() {
        resetQuotaForm();
        showQuotaForm();
    });

    // Handle cancel button click
    $('#cancelQuotaBtn').on('click', function() {
        resetQuotaForm();
    });

    // Handle edit quota button click
    $(document).on('click', '.edit-quota-button', function() {
        const quotaId = $(this).closest('tr').data('id');
        
        $.ajax({
            url: '../api/sales_data_management.php',
            method: 'GET',
            data: { action: 'get_quota', id: quotaId },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    const quota = response.data;
                    $('#quotaId').val(quota.id);
                    $('#quotaYear').val(quota.year);
                    $('#quotaBranch').val(quota.branch);
                    $('#quotaAmount').val(quota.quota);
                    showQuotaForm();
                } else {
                    showErrorModal(response.message || 'Failed to load quota data');
                }
            },
            error: function(xhr, status, error) {
                showErrorModal('Error loading quota: ' + error);
            }
        });
    });

    // Handle quota search
    $('#quotaSearchBtn').on('click', function() {
        const query = $('#quotaSearchInput').val();
        loadQuotas(query);
    });

    $('#quotaSearchInput').on('keyup', function(e) {
        if (e.key === 'Enter') {
            const query = $(this).val();
            loadQuotas(query);
        }
    });

    // Handle quota form submission
    $('#salesQuotaForm').submit(function(e) {
        e.preventDefault();
        
        const formData = {
            action: 'set_quota',
            id: $('#quotaId').val(),
            year: $('#quotaYear').val(),
            branch: $('#quotaBranch').val(),
            quota: $('#quotaAmount').val()
        };
        
        $.ajax({
            url: '../api/sales_data_management.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#quotaSuccessMessage').text(response.message || 'Quota saved successfully!').show();
                    loadQuotas($('#quotaSearchInput').val());
                    resetQuotaForm();
                    setTimeout(() => {
                        $('#quotaSuccessMessage').hide();
                    }, 3000);
                } else {
                    $('#quotaErrorMessage').text(response.message || 'Failed to save quota').show();
                    setTimeout(() => {
                        $('#quotaErrorMessage').hide();
                    }, 3000);
                }
            },
            error: function(xhr, status, error) {
                $('#quotaErrorMessage').text('Error: ' + error).show();
                setTimeout(() => {
                    $('#quotaErrorMessage').hide();
                }, 3000);
            }
        });
    });

    // Handle delete quota button click
    $(document).on('click', '.delete-quota-button', function() {
        const quotaId = $(this).closest('tr').data('id');
        
        if (confirm('Are you sure you want to delete this quota?')) {
            $.ajax({
                url: '../api/sales_data_management.php',
                method: 'POST',
                data: { 
                    action: 'delete_quota',
                    id: quotaId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        loadQuotas();
                        showSuccessModal('Quota deleted successfully!');
                    } else {
                        showErrorModal(response.message || 'Failed to delete quota');
                    }
                },
                error: function(xhr, status, error) {
                    showErrorModal('Error deleting quota: ' + error);
                }
            });
        }
    });
}

function populateBranchDropdown() {
    const branches = [
          'RXS-S', 'RXS-H', 'ANT-1', 'ANT-2', 'SDH', 'SDS', 'JAR-1', 'JAR-2',
    'SKM', 'SKS', 'ALTA', 'EMAP', 'CUL', 'BAC', 'PAS-1', 'PAS-2',
    'BAL', 'GUIM', 'PEMDI', 'EEM', 'AJUY', 'BAIL', '3SMB', '3SMIN',
    'MAN', 'K-RID', 'IBAJAY', 'NUM', 'HO', 'TTL', 'CEBU', 'GT'
    ];
    
    const $branchDropdown = $('#quotaBranch');
    $branchDropdown.empty();
    
    branches.forEach(branch => {
        $branchDropdown.append($('<option>', {
            value: branch,
            text: branch
        }));
    });
}

function resetQuotaForm() {
    $('#quotaId').val('');
    $('#quotaYear').val('');
    $('#quotaBranch').val('');
    $('#quotaAmount').val('');
    $('#quotaFormContainer').hide();
    $('#quotaErrorMessage').hide();
    $('#quotaSuccessMessage').hide();
}

function showQuotaForm() {
    $('#quotaFormContainer').show();
    $('#quotaFormContainer')[0].scrollIntoView({ behavior: 'smooth' });
}

function loadQuotas(query = '') {
    $.ajax({
        url: '../api/sales_data_management.php',
        method: 'GET',
        data: { 
            action: 'get_quotas',
            query: query
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#quotasTableBody').html(generateQuotaTableRows(response.data));
            } else {
                showErrorModal(response.message || 'Failed to load quotas');
            }
        },
        error: function(xhr, status, error) {
            showErrorModal('Error loading quotas: ' + error);
        }
    });
}

function generateQuotaTableRows(quotas) {
    let rows = '';
    quotas.forEach(quota => {
        rows += `
            <tr data-id="${quota.id}">
                <td>${quota.year}</td>
                <td>${quota.branch}</td>
                <td>${quota.quota}</td>
                <td>
                    <div class="btn-group btn-group-sm" role="group">
                        <button class="btn btn-outline-primary edit-quota-button">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-outline-danger delete-quota-button">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });
    return rows;
}

// ==================== SUMMARY REPORT FUNCTIONS ====================
function initSummaryReport() {
    // Generate report button click handler
    $('#generateSummaryBtn').on('click', function() {
        generateSummaryReport();
    });

    // Export to Excel button
    $('#exportExcelBtn').on('click', function() {
        exportReport('excel');
    });

    // Export to PDF button
    $('#exportPdfBtn').on('click', function() {
        exportReport('pdf');
    });
}

function generateSummaryReport() {
    const year = $('#summaryYear').val();
    const branch = $('#summaryBranchFilter').val();
    const brand = $('#summaryBrandFilter').val();
    const fromDate = $('#fromDate').val();
    const toDate = $('#toDate').val();



    showLoading(true, '#summaryReportBody');
    
    $.ajax({
        url: '../api/sales_data_management.php',
        method: 'GET',
        data: {
            action: 'get_summary_report',
            year: year,
            branch: branch,
            brand: brand,
            fromDate: fromDate,
            toDate: toDate
        },
        dataType: 'json',
        success: function(response) {
            showLoading(false, '#summaryReportBody');
            if (response.success) {
                // Update the record count
                $('#recordCount').text(response.total_records + ' records');
                
                // Render the report data
                renderSummaryReport(response.data);
            } else {
                showErrorModal(response.message || 'Failed to generate report');
            }
        },
        error: function(xhr, status, error) {
            showLoading(false, '#summaryReportBody');
            showErrorModal('Error generating report: ' + error);
        }
    });
}

function renderSummaryReport(data) {
    const $tbody = $('#summaryReportBody');
    $tbody.empty();

    if (!data || data.length === 0) {
        $tbody.html('<tr><td colspan="5" class="text-center py-5 text-muted">No data available for the selected criteria</td></tr>');
        return;
    }

    data.forEach(item => {
        $tbody.append(`
            <tr>
                <td>${item.branch}</td>
                <td>${item.brand}</td>
                <td>${item.model}</td>
                <td>${item.qty}</td>
                <td>${new Date(item.sales_date).toLocaleDateString()}</td>
            </tr>
        `);
    });
}

function exportReport(format) {
    const branch = $('#summaryBranchFilter').val();
    const brand = $('#summaryBrandFilter').val();
    const month = $('#summaryMonthFilter').val();
    const year = $('#summaryYearFilter').val();

    // Show loading state
    const btn = format === 'excel' ? $('#exportExcelBtn') : $('#exportPdfBtn');
    const originalText = btn.html();
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');

    // Build export URL
    let url = `../api/export_summary.php?format=${format}`;
    if (month && month !== 'all') url += `&month=${month}`;
    if (year) url += `&year=${year}`;
    if (branch && branch !== 'all') url += `&branch=${encodeURIComponent(branch)}`;
    if (brand && brand !== 'all') url += `&brand=${encodeURIComponent(brand)}`;

    // Create hidden iframe for download
    const iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    document.body.appendChild(iframe);

    // First check if data exists
    fetch(url, {
        method: 'HEAD',
        cache: 'no-cache'
    })
    .then(response => {
        if (response.ok) {
            // Data exists - trigger download
            iframe.src = url;
            
            // Set timeout to reset button
            setTimeout(() => {
                btn.prop('disabled', false).html(originalText);
                setTimeout(() => document.body.removeChild(iframe), 5000);
            }, 3000);
        } else if (response.status === 404) {
            // No data found
            showWarningModal(`No sales data found for ${month}/${year}`);
            btn.prop('disabled', false).html(originalText);
            document.body.removeChild(iframe);
        } else {
            throw new Error('Export failed');
        }
    })
    .catch(error => {
        showWarningModal(error.message || 'Error generating report');
        btn.prop('disabled', false).html(originalText);
        document.body.removeChild(iframe);
    });
}

function showLoading(show, element) {
    if (show) {
        $(element).html('<tr><td colspan="5" class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>');
    }
}

// ==================== MODAL FUNCTIONS ====================
function initModals() {

    const modalElements = document.querySelectorAll('.modal');
    modalElements.forEach(modalEl => {
        new bootstrap.Modal(modalEl);
    });
    // Handle add sale form submission
    $('#addSaleForm').submit(function(e) {
        e.preventDefault();
        
        const formData = {
            action: 'add_sale',
            sales_date: $('#saleDate').val(),
            branch: $('#branch').val(),
            brand: $('#brand').val(),
            model: $('#model').val(),
            qty: $('#quantity').val()
        };
        
        $.ajax({
            url: '../api/sales_data_management.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#addSaleModal').modal('hide');
                    $('#addSaleForm')[0].reset();
                    loadSales();
                    showSuccessModal('Sale added successfully!');
                } else {
                    if (response.message.includes('duplicate')) {
                        showDuplicateErrorModal(response.message);
                    } else {
                        showErrorModal(response.message || 'Failed to add sale');
                    }
                }
            },
            error: function(xhr, status, error) {
                showErrorModal('Error: ' + error);
            }
        });
    });

   // Handle edit button click
// Handle edit button click
$(document).on('click', '.edit-button', function() {
    const saleId = $(this).closest('tr').data('id');
    
    $.ajax({
        url: '../api/sales_data_management.php',
        method: 'GET',
        data: { action: 'get_sale', id: saleId },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                const sale = response.data;
                $('#editSaleId').val(sale.id);
                $('#editSaleDate').val(sale.sales_date);
                $('#editBranch').val(sale.branch);
                $('#editBrand').val(sale.brand);
                
                // Update models dropdown for the selected brand with the current model as default
                updateModelsDropdown(sale.brand, $('#editModel'), sale.model);
                
                $('#editQuantity').val(sale.qty);
                $('#editSaleModal').modal('show');
            } else {
                showErrorModal(response.message || 'Failed to load sale data');
            }
        },
        error: function(xhr, status, error) {
            showErrorModal('Error loading sale: ' + error);
        }
    });
});

    // Handle edit sale form submission
    $('#editSaleForm').submit(function(e) {
        e.preventDefault();
        
        const formData = {
            action: 'update_sale',
            id: $('#editSaleId').val(),
            sales_date: $('#editSaleDate').val(),
            branch: $('#editBranch').val(),
            brand: $('#editBrand').val(),
            model: $('#editModel').val(),
            qty: $('#editQuantity').val()
        };
        
        $.ajax({
            url: '../api/sales_data_management.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#editSaleModal').modal('hide');
                    loadSales();
                    showSuccessModal('Sale updated successfully!');
                } else {
                    showErrorModal(response.message || 'Failed to update sale');
                }
            },
            error: function(xhr, status, error) {
                showErrorModal('Error updating sale: ' + error);
            }
        });
    });

    // Handle delete button click
    $(document).on('click', '.delete-button', function() {
        saleIdToDelete = $(this).closest('tr').data('id');
        $('#confirmationModal').modal('show');
    });

    // Handle delete selected button click
    $('#deleteSelectedButton').on('click', function() {
        if (selectedRecordIds.length > 0) {
            $('#confirmationModal').modal('show');
        } else {
            showWarningModal('No sales selected for deletion.');
        }
    });

    // Handle confirm delete button click
    $('#confirmDeleteBtn').on('click', function() {
        const idsToDelete = saleIdToDelete ? [saleIdToDelete] : selectedRecordIds;
        
        if (idsToDelete.length > 0) {
            $.ajax({
                url: '../api/sales_data_management.php',
                method: 'POST',
                data: { 
                    action: 'delete_sale',
                    ids: idsToDelete
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#confirmationModal').modal('hide');
                        loadSales();
                        showSuccessModal(response.message || 'Sales deleted successfully!');
                    } else {
                        showErrorModal(response.message || 'Failed to delete sales');
                    }
                },
                error: function(xhr, status, error) {
                    showErrorModal('Error deleting sales: ' + error);
                },
                complete: function() {
                    saleIdToDelete = null;
                    selectedRecordIds = [];
                    $('#selectAll').prop('checked', false);
                }
            });
        }
    });

    // Print options functionality
    $('#sortBy').on('change', function() {
        const selectedValue = $(this).val();
        
        // Hide all range selectors
        $('#dateRange').hide();
        $('#branchSelection').hide();
        
        // Show the appropriate range based on the selected value
        if (selectedValue === 'dateRange') {
            $('#dateRange').show();
        } else if (selectedValue === 'branch') {
            $('#branchSelection').show();
        }
    });

    // Handle print confirmation
    $('#confirmPrint').on('click', function() {
        const fromDate = $('#fromDate').val();
        const toDate = $('#toDate').val();
        const branch = $('#branchSelect').val();
        const outputFormat = $('#outputFormat').val();

        if (!fromDate || !toDate) {
            showWarningModal('Please select both From and To dates.');
            return;
        }

        // Redirect to generate report script
        window.location.href = `../api/export_summary.php?fromDate=${encodeURIComponent(fromDate)}&toDate=${encodeURIComponent(toDate)}&branch=${encodeURIComponent(branch)}&format=${encodeURIComponent(outputFormat)}`;
        
        $('#printOptionsModal').modal('hide');
    });
}

// ==================== HELPER FUNCTIONS ====================
function showSuccessModal(message) {
    $('#successMessage').text(message);
    $('#successModal').modal('show');
    setTimeout(() => {
        $('#successModal').modal('hide');
    }, 2000);
}

function showErrorModal(message) {
    $('#errorMessage').text(message);
    $('#errorMessage').show();
    setTimeout(() => {
        $('#errorMessage').hide();
    }, 3000);
}

function showDuplicateErrorModal(message) {
    $('#duplicateErrorMessage').text(message);
    $('#duplicateErrorModal').modal('show');
}

function showWarningModal(message) {
    const modal = $('#warningModal');
    if (modal.length) {
        $('#warningMessage').text(message);
        modal.modal('show');
        
        // Auto-hide after 5 seconds
        setTimeout(() => modal.modal('hide'), 5000);
    } else {
        // Fallback if modal not available
        alert('Warning: ' + message);
    }
}