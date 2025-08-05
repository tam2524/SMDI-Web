// Define the models for each brand
const brandModels = {
    "Suzuki": ["GSX-250RL/FRLX", "GSX-150", "BIGBIKE", "GSX150FRF NEW", "GSX-S150", "UX110NER", "UB125", "AVENIS", "FU150", "FU150-FI", "FW110D", "FW110SD/SC", "DS250RL", "FJ110 LB-2", "FW110D(SMASH FI)", "FJ110LX", "UB125LNM(NEW)", "UK110", "UX110", "UK125", "GD110"],
    "Honda": ["GIORNO+", "CCG 125", "CFT125MRCS", "AFB110MDJ", "AFS110MDJ", "AFB110MDH", "CFT125MSJ", "AFS110MCDE", "MRCP", "DIO", "MSM", "MRP", "MRS", "CFT125MRCJ", "MSP", "MSS", "AFP110DFP", "MRCP", "AFP110DFR", "ZN125", "PCX160NEW", "PCX160", "AFB110MSJ", "AFP110SFR", "AFP110SFP", "CBR650", "CB500", "CB650R", "GL150R", "CBR500", "AIRBLADE 150", "AIRBLADE160", "ADV160", "CBR150RMIV/RAP", "BEAT-CSFN/FR/R3/FS/3", "CB150X", "WINNER X", "CRF-150", "CRF300", "CMX500", "XR150", "ACB160", "ACB125"],
    "Yamaha": ["MIO SPORTY", "MIOI125", "MIO GEAR", "SNIPER", "MIO GRAVIS", "YTX", "YZF R3", "FAZZIO", "XSR", "VEGA", "AEROX", "XTZ", "NMAX", "PG-1 BRN1", "MT-15", "FZ", "R15M BNE1/2", "XMAX", "WR155", "SEROW"],
    "Kawasaki": ["CT100 A", "CT100B", "CT125", "CA100AA NEW", "BC175H/MS", "BC175J/NN/SN", "BC175 III ELECT.", "BC175 III KICK", "BRUSKY", "NS125", "ELIMINATOR SE", "CT100B", "NINJA ZX 4RR", "Z900 SE", "KLX140", "KLX150", "CT150BA", "ROUSER 200", "W800", "VERYS 650", "KLX232", "NINJA ZX-10R", "Z900 SE"]
};

// Global variables
let selectedRecordIds = [];
let saleIdToDelete = null;
let currentPage = 1;

$(document).ready(function() {
    // Initialize models dropdown on page load
    updateModelsDropdown($('#brand').val(), $('#model'));
    initModals();
    
    // Initialize modals and event handlers
    initSalesTable();
    initQuotaManagement();
    initSummaryReport();
    
    // Initial load of sales
    loadSales();
});


// ==================== MODAL FUNCTIONS ====================
function initModals() {
    // Initialize all modals
    $('.modal').modal({
        show: false
    });

    // Handle add sale modal
    $('#addSaleModal').on('shown.bs.modal', function() {
        // Reset form and update models dropdown
        $('#addSaleForm')[0].reset();
        updateModelsDropdown($('#brand').val(), $('#model'));
    });

    // Handle edit sale modal
    $(document).on('click', '.edit-button', function() {
        const saleId = $(this).closest('tr').data('id');
        
        $.ajax({
            url: 'api/sales_data_management.php',
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
                    $('#editmodel').val(sale.model);
                    $('#editQuantity').val(sale.qty);
                    
                    updateModelsDropdown(sale.brand, $('#editmodel'));
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

    // Handle upload sales data modal
    $('#uploadSalesBtn').on('click', function() {
        $('#uploadSalesDataModal').modal('show');
    });

    // Handle confirmation modal
    $(document).on('click', '.delete-button', function() {
        saleIdToDelete = $(this).closest('tr').data('id');
        $('#confirmationModal').modal('show');
    });

    // Handle print options modal
    $('#printOptionsBtn').on('click', function() {
        $('#printOptionsModal').modal('show');
    });

    // ... rest of your modal initialization code ...
}

// Make sure to call initModals() in your $(document).ready()
$(document).ready(function() {
    // Initialize models dropdown on page load
    updateModelsDropdown($('#brand').val(), $('#model'));
    
    // Initialize modals and event handlers
    initSalesTable();
    initQuotaManagement();
    initSummaryReport();
    initModals(); // THIS IS CRUCIAL
    
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
        updateModelsDropdown($(this).val(), $('#editmodel'));
    });

    // Search input event
    $('#searchInput').on('input', function() {
        const query = $(this).val();
        currentPage = 1; // Reset to first page when searching
        loadSales(query);
    });

    // Pagination click events
    $(document).on('click', '.page-link', function(e) {
        e.preventDefault();
        if ($(this).parent().hasClass('disabled')) return;
        
        if ($(this).attr('id') === 'prevPage') {
            currentPage--;
        } else if ($(this).attr('id') === 'nextPage') {
            currentPage++;
        } else {
            currentPage = parseInt($(this).data('page'));
        }
        
        loadSales($('#searchInput').val(), currentPage);
    });

    // Sorting functionality
    $('.dropdown-item').on('click', function(e) {
        e.preventDefault();
        const sortOption = $(this).data('sort');
        currentPage = 1; // Reset to first page when sorting
        loadSales($('#searchInput').val(), currentPage, sortOption);
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
            url: 'api/sales_data_management.php',
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
function loadSales(query = '', page = currentPage, sort = '') {
    $.ajax({
        url: 'api/sales_data_management.php',
        method: 'GET',
        data: { 
            action: 'get_sales',
            query: query,
            page: page,
            sort: sort
        },
        success: function(response) {
            if (response.success) {
                $('#salesTableBody').html(generateTableRows(response.data));
                updatePaginationControls(response.totalPages);
            } else {
                showErrorModal(response.message || 'Failed to load sales data');
            }
        },
        error: function(xhr, status, error) {
            showErrorModal('Error loading sales: ' + error);
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
    
    // Previous button
    paginationHtml += `
        <li id="prevPage" class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a>
        </li>
    `;
    
    // Page numbers
    for (let i = 1; i <= totalPages; i++) {
        paginationHtml += `
            <li class="page-item ${currentPage === i ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>
        `;
    }
    
    // Next button
    paginationHtml += `
        <li id="nextPage" class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#">Next</a>
        </li>
    `;
    
    $('#paginationControls').html(paginationHtml);
}

function updateModelsDropdown(brand, $dropdown) {
    $dropdown.empty();
    if (brand && brandModels[brand]) {
        brandModels[brand].forEach(model => {
            $dropdown.append($('<option>', {
                value: model,
                text: model
            }));
        });
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
        url: 'api/sales_data_management.php',
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
        populateYearDropdown();
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
            url: 'api/sales_data_management.php',
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
            url: 'api/sales_data_management.php',
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
                url: 'api/sales_data_management.php',
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

function populateYearDropdown() {
    const currentYear = new Date().getFullYear();
    const $yearDropdown = $('#quotaYear');
    const $summaryYearDropdown = $('#summaryYear');

    $yearDropdown.empty();
    
    // Add options for current year and next 5 years
    for (let i = 0; i < 6; i++) {
        const year = currentYear + i;
        $yearDropdown.append($('<option>', {
            value: year,
            text: year
        }));
        $summaryYearDropdown.append($('<option>', {
            value: year,
            text: year
        }));
    }
}

function populateBranchDropdown() {
    const branches = [
        "RXS-1", "RXS-2", "ANTIQUE-1", "ANTIQUE-2", "DELGADO-1", "DELGADO-2",
        "JARO-1", "JARO-2", "KALIBO-1", "KALIBO-2", "ALTAVAS", "EMAP", "CULASI",
        "BACOLOD", "PASSI-1", "PASSI-2", "BALASAN", "GUIMARAS", "PEMDI", "EEMSI",
        "AJUY", "BAILAN", "MINDORO MB", "MINDORO 3S", "MANSALAY", "K-RIDERS",
        "IBAJAY", "NUMANCIA", "HEADOFFICE", "CEBU", "GT"
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
        url: 'api/sales_data_management.php',
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
    // Initialize summary modal
    $('#summaryReportModal').on('shown.bs.modal', function() {
        populateYearDropdown('#summaryYear');
        populateBranchDropdown('#summaryBranchFilter');
    });

     // Populate branch dropdown
    const branches = [
        "RXS-1", "RXS-2", "ANTIQUE-1", "ANTIQUE-2", "DELGADO-1", "DELGADO-2",
        "JARO-1", "JARO-2", "KALIBO-1", "KALIBO-2", "ALTAVAS", "EMAP", "CULASI",
        "BACOLOD", "PASSI-1", "PASSI-2", "BALASAN", "GUIMARAS", "PEMDI", "EEMSI",
        "AJUY", "BAILAN", "MINDORO MB", "MINDORO 3S", "MANSALAY", "K-RIDERS",
        "IBAJAY", "NUMANCIA", "HEADOFFICE", "CEBU", "GT"
    ];
    
    const $branchDropdown = $('#summaryBranchFilter');
    branches.forEach(branch => {
        $branchDropdown.append($('<option>', {
            value: branch,
            text: branch
        }));
    });

    // Generate report button click handler
    $('#generateSummaryBtn').on('click', function() {
        const year = $('#summaryYear').val();
        const month = $('#summaryMonth').val();
        const branchFilter = $('#summaryBranchFilter').val();
        
        if (!year) {
            showWarningModal('Please select a year');
            return;
        }
        
        generateSummaryReport(year, month, branchFilter);
    });

    // Export buttons
    $('#exportExcelBtn').on('click', function() {
        const year = $('#summaryYear').val();
        const month = $('#summaryMonth').val();
        const branch = $('#summaryBranchFilter').val();
        window.location.href = `api/export_summary.php?year=${year}&month=${month}&branch=${branch}&format=excel`;
    });

    $('#exportPdfBtn').on('click', function() {
        const year = $('#summaryYear').val();
        const month = $('#summaryMonth').val();
        const branch = $('#summaryBranchFilter').val();
        window.location.href = `api/export_summary.php?year=${year}&month=${month}&branch=${branch}&format=pdf`;
    });
}

function generateSummaryReport() {
    const year = $('#summaryYear').val();
    const branchFilter = $('#summaryBranchFilter').val();
    const fromDate = $('#fromDate').val();
    const toDate = $('#toDate').val();

    showLoading(true);
    
    $.ajax({
        url: 'api/sales_data_management.php',
        method: 'GET',
        data: {
            action: 'get_summary_report',
            year: year,
            branch: branchFilter,
            fromDate: fromDate,
            toDate: toDate
        },
        dataType: 'json',
        success: function(response) {
            showLoading(false);
            if (response.success) {
                // Update the record count
                $('#recordCount').text(response.data.sales.length + ' records');
                
                // Render the report data
                renderSummaryReport(response.data);
            } else {
                showErrorModal(response.message || 'Failed to generate report');
            }
        },
        error: function(xhr, status, error) {
            showLoading(false);
            showErrorModal('Error generating report: ' + error);
        }
    });
}
function renderSummaryReport(data) {
    const $tbody = $('#summaryReportBody');
    $tbody.empty();

    if (!data || !data.sales || data.sales.length === 0) {
        $tbody.html('<tr><td colspan="4" class="text-center py-5 text-muted">No data available for the selected criteria</td></tr>');
        return;
    }

    // Calculate totals
    let grandTotal = 0;
    const branchTotals = {};
    const modelTotals = {};

    // First pass to calculate totals
    data.sales.forEach(sale => {
        const qty = parseInt(sale.qty);
        grandTotal += qty;

        // Branch totals
        if (!branchTotals[sale.branch]) {
            branchTotals[sale.branch] = 0;
        }
        branchTotals[sale.branch] += qty;

        // Model totals
        if (!modelTotals[sale.model]) {
            modelTotals[sale.model] = 0;
        }
        modelTotals[sale.model] += qty;
    });

    // Second pass to create rows
    data.sales.forEach(sale => {
        $tbody.append(`
            <tr>
                <td>${sale.branch}</td>
                <td>${sale.model}</td>
                <td>${sale.qty}</td>
                <td>${sale.brand}</td>
            </tr>
        `);
    });

    // Add summary row
    $tbody.append(`
        <tr class="table-primary">
            <td><strong>Total</strong></td>
            <td></td>
            <td><strong>${grandTotal}</strong></td>
            <td></td>
        </tr>
    `);
}

function showLoading(show) {
    if (show) {
        $('#summaryReportBody').html('<tr><td colspan="100" class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>');
    }
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
    $('#warningMessage').text(message);
    $('#warningModal').modal('show');
}