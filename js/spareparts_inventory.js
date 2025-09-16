$(document).ready(function() {
    // Initial data load on page load
    loadDashboard();
    setupEventListeners();
});

function setupEventListeners() {
    // --- Tab Switching Event Listener ---
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
        if (e.target.id === 'dashboard-tab') {
            loadDashboard();
        }
    });

    // --- Form Submission Event Listeners ---
    $('#addPartsInForm').submit(function(e) {
        e.preventDefault();
        submitForm($(this));
    });

    $('#sellPartsOutForm').submit(function(e) {
        e.preventDefault();
        submitForm($(this));
    });

    $('#recordPaymentForm').submit(function(e) {
        e.preventDefault();
        submitForm($(this));
    });

    $('#transferPartsForm').submit(function(e) {
        e.preventDefault();
        submitForm($(this));
    });

    // --- Reports Tab Event Listeners ---
    $('#reportPeriod').change(function() {
        if ($(this).val() === 'daily') {
            $('#reportDay').removeClass('d-none').attr('required', true);
            $('#reportMonth').addClass('d-none').removeAttr('required');
        } else {
            $('#reportMonth').removeClass('d-none').attr('required', true);
            $('#reportDay').addClass('d-none').removeAttr('required');
        }
    }).change();

    $('#generateReportBtn').click(function() {
        generateReport();
    });

    // Populate branch dropdown for transfers when the modal is shown
    $('#transferPartsModal').on('show.bs.modal', function() {
        populateBranchesDropdown();
    });
}

/**
 * Generic function to handle form submission via AJAX.
 * It automatically determines the action from the form's ID.
 * @param {jQuery} $form The jQuery object for the form.
 */
function submitForm($form) {
    let action;
    const formId = $form.attr('id');
    switch (formId) {
        case 'addPartsInForm':
            action = 'add_parts_in';
            break;
        case 'sellPartsOutForm':
            action = 'sell_parts_out';
            break;
        case 'recordPaymentForm':
            action = 'record_payment';
            break;
        case 'transferPartsForm':
            action = 'transfer_parts';
            break;
        default:
            showErrorModal('Invalid form submission.');
            return;
    }

    const formData = $form.serialize() + '&action=' + action;

    $.ajax({
        url: '../api/spareparts_inventory.php',
        method: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showSuccessModal(response.message);
                $form.closest('.modal').modal('hide');
                $form[0].reset();
                loadDashboard();
            } else {
                showErrorModal(response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', xhr.responseText);
            showErrorModal('API error: ' + error + '. Check console for details.');
        }
    });
}


function loadDashboard() {
    const tableBody = $('#partsDashboardBody');
    tableBody.html(`<tr><td colspan="8" class="text-center py-5"><div class='spinner-border text-primary' role='status'><span class='visually-hidden'>Loading...</span></div></td></tr>`);

    $.ajax({
        url: '../api/spareparts_inventory.php',
        method: 'GET',
        data: { action: 'get_inventory_dashboard' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                renderDashboard(response.data);
            } else {
                showErrorModal(response.message || 'Error loading dashboard.');
                tableBody.html('<tr><td colspan="8" class="text-center text-muted">Failed to load data.</td></tr>');
            }
        },
        error: function(xhr, status, error) {
            showErrorModal('API error: ' + error);
            tableBody.html('<tr><td colspan="8" class="text-center text-muted">API connection failed.</td></tr>');
        }
    });
}

function renderDashboard(data) {
    const tableBody = $('#partsDashboardBody');
    tableBody.empty();
    if (data.length === 0) {
        tableBody.html('<tr><td colspan="8" class="text-center text-muted">No spare parts in inventory.</td></tr>');
        return;
    }

    data.forEach(part => {
        const totalValue = part.current_stock * part.cost;
        const status = part.current_stock < part.min_stock ? `<span class="badge bg-danger">Low Stock</span>` : `<span class="badge bg-success">In Stock</span>`;
        tableBody.append(`
            <tr>
                <td>${escapeHtml(part.part_no)}</td>
                <td>${escapeHtml(part.description)}</td>
                <td>${part.current_stock}</td>
                <td>₱${formatCurrency(part.cost)}</td>
                <td>₱${formatCurrency(totalValue)}</td>
                <td>${status}</td>
            </tr>
        `);
    });
}

// Report Generation
function generateReport() {
    const reportType = $('#reportType').val();
    const reportPeriod = $('#reportPeriod').val();
    const reportDate = reportPeriod === 'daily' ? $('#reportDay').val() : $('#reportMonth').val();

    if (!reportDate) {
        showErrorModal('Please select a date or month for the report.');
        return;
    }

    let action;
    let data = {};
    if (reportType === 'aging') {
        action = 'get_aging_report';
    } else if (reportType === 'sales') {
        action = 'get_sales_report';
    } else if (reportType === 'payments') {
        action = 'get_payments_report';
    }

    if (reportPeriod === 'daily') {
        data.date = reportDate;
    } else {
        data.month = reportDate;
    }

    $('#reportResults').html(`<div class="text-center py-4"><div class='spinner-border text-primary' role='status'></div><p class="mt-2">Generating report...</p></div>`);

    $.ajax({
        url: '../api/spareparts_inventory.php',
        method: 'GET',
        data: { action: action, ...data },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                renderReport(reportType, response.data, reportDate, reportPeriod);
            } else {
                showErrorModal(response.message || 'Error generating report.');
                $('#reportResults').html(`<div class="alert alert-danger">Error: ${response.message}</div>`);
            }
        },
        error: function() {
            showErrorModal('API error while generating report.');
            $('#reportResults').html(`<div class="alert alert-danger">API connection failed.</div>`);
        }
    });
}

function renderReport(reportType, data, period, periodType) {
    let reportTitle = '';
    let tableHeaders = '';
    let tableRows = '';

    if (reportType === 'aging') {
        reportTitle = `Aging Report (Outstanding Balances)`;
        tableHeaders = `<th>OR Number</th><th>Customer Name</th><th>Sale Date</th><th>Total Amount</th><th>Balance</th>`;
        if (data.length === 0) {
            tableRows = `<tr><td colspan="5" class="text-center text-muted">No outstanding balances found.</td></tr>`;
        } else {
            data.forEach(item => {
                tableRows += `<tr>
                    <td>${escapeHtml(item.or_number)}</td>
                    <td>${escapeHtml(item.customer_name)}</td>
                    <td>${item.sale_date}</td>
                    <td>₱${formatCurrency(item.total_amount)}</td>
                    <td>₱${formatCurrency(item.balance)}</td>
                </tr>`;
            });
        }
    } else if (reportType === 'sales') {
        reportTitle = `Sales Report for ${period}`;
        tableHeaders = `<th>Part No</th><th>Quantity</th><th>Unit Price</th><th>Total Amount</th><th>Customer Name</th><th>OR Number</th><th>Date</th>`;
        if (data.length === 0) {
            tableRows = `<tr><td colspan="7" class="text-center text-muted">No sales found for this period.</td></tr>`;
        } else {
            data.forEach(item => {
                tableRows += `<tr>
                    <td>${escapeHtml(item.part_no)}</td>
                    <td>${item.quantity}</td>
                    <td>₱${formatCurrency(item.unit_price)}</td>
                    <td>₱${formatCurrency(item.total_amount)}</td>
                    <td>${escapeHtml(item.customer_name)}</td>
                    <td>${escapeHtml(item.or_number)}</td>
                    <td>${item.transaction_date}</td>
                </tr>`;
            });
        }
    } else if (reportType === 'payments') {
        reportTitle = `Payments Summary for ${period}`;
        tableHeaders = `<th>Date</th><th>Customer Name</th><th>Amount</th><th>OR Number</th>`;
        if (data.length === 0) {
            tableRows = `<tr><td colspan="4" class="text-center text-muted">No payments found for this period.</td></tr>`;
        } else {
            data.forEach(item => {
                tableRows += `<tr>
                    <td>${item.transaction_date}</td>
                    <td>${escapeHtml(item.customer_name)}</td>
                    <td>₱${formatCurrency(item.total_amount)}</td>
                    <td>${escapeHtml(item.or_number)}</td>
                </tr>`;
            });
        }
    }

    const reportHtml = `
        <h5>${reportTitle}</h5>
        <div class="table-responsive">
            <table class="table table-striped table-bordered table-sm">
                <thead class="table-dark">
                    <tr>${tableHeaders}</tr>
                </thead>
                <tbody>
                    ${tableRows}
                </tbody>
            </table>
        </div>
    `;
    $('#reportResults').html(reportHtml);
}

// Helper Functions
function populateBranchesDropdown() {
    const branches = [
        "HEADOFFICE", "KINGDOM", "TANQUE", "DFISHER", "ROXAS SUZUKI", "MAMBUSAO", "SIGMA",
        "PRC", "BAILAN", "CUARTERO", "JAMINDAN", "ROXAS HONDA", "ANTIQUE-1", "ANTIQUE-2",
        "DELGADO HONDA", "DELGADO SUZUKI", "JARO-1", "JARO-2", "KALIBO MABINI", "KALIBO SUZUKI",
        "ALTAVAS", "EMAP", "CULASI", "BACOLOD", "PASSI-1", "PASSI-2", "BALASAN", "GUIMARAS",
        "PEMDI BACOLOD", "INFINITY BACOLOD", "EEMSI-GUIMARAS", "AJUY", "MINDORO-MB", "MINDORO ROXAS",
        "3S MINDORO", "MINDORO MANSALAY", "K-RIDERS ROXAS", "IBAJAY", "NUMANCIA", "CFCIPRC"
    ];
    const dropdown = $('#transfer_to_branch');
    dropdown.empty();
    dropdown.append('<option value="">Select Destination Branch</option>');
    branches.forEach(branch => {
        if (branch !== currentBranch) {
            dropdown.append(`<option value="${escapeHtml(branch)}">${escapeHtml(branch)}</option>`);
        }
    });
}

function showSuccessModal(message) {
    $('#successMessage').text(message);
    $('#successModal').modal('show');
}

function showErrorModal(message) {
    $('#errorMessage').text(message);
    $('#errorModal').modal('show');
}

function formatCurrency(amount) {
    return parseFloat(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
}