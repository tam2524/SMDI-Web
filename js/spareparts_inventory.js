// spareparts_inventory.js

// Global variables
let currentPage = 1;
let itemsPerPage = 10;
let totalItems = 0;
let currentSortField = 'created_at';
let currentSortOrder = 'desc';
let sparepartsInData = [];
let salesData = [];
let paymentsData = [];
let transfersData = [];

// Document ready function
$(document).ready(function() {
    initializeApp();
    setupEventListeners();
    loadSparepartsInData();
    loadDashboardStats();
});

function initializeApp() {
    // Set current date for date fields
    const today = new Date().toISOString().split('T')[0];
    $('#dateReceived').val(today);
    $('#saleDate').val(today);
    $('#paymentDate').val(today);
    $('#transferDate').val(today);
    
    // Initialize tab functionality
    $('#inventoryTabs button').on('click', function(e) {
        const target = $(this).data('bs-target');
        
        // Load data based on the selected tab
        if (target === '#spareparts-in') {
            loadSparepartsInData();
        } else if (target === '#sales') {
            loadSalesData();
        } else if (target === '#payments') {
            loadPaymentsData();
        } else if (target === '#transfers') {
            loadTransfersData();
        } else if (target === '#reports') {
            // Reports tab - no data loading needed
        }
    });
}

function setupEventListeners() {
    // Search functionality
    $('#searchSparepartsInBtn').click(() => searchSparepartsIn($('#searchSparepartsIn').val()));
    $('#searchSparepartsIn').on('keypress', (e) => {
        if (e.which === 13) searchSparepartsIn($('#searchSparepartsIn').val());
    });
    
    $('#searchSalesBtn').click(() => searchSales($('#searchSales').val()));
    $('#searchSales').on('keypress', (e) => {
        if (e.which === 13) searchSales($('#searchSales').val());
    });
    
    $('#searchPaymentsBtn').click(() => searchPayments($('#searchPayments').val()));
    $('#searchPayments').on('keypress', (e) => {
        if (e.which === 13) searchPayments($('#searchPayments').val());
    });
    
    $('#searchTransfersBtn').click(() => searchTransfers($('#searchTransfers').val()));
    $('#searchTransfers').on('keypress', (e) => {
        if (e.which === 13) searchTransfers($('#searchTransfers').val());
    });
    
    // Pagination
    $('#prevPage').click(() => {
        if (currentPage > 1) {
            currentPage--;
            loadCurrentTabData();
        }
    });
    
    $('#nextPage').click(() => {
        if (currentPage < Math.ceil(totalItems / itemsPerPage)) {
            currentPage++;
            loadCurrentTabData();
        }
    });
    
    // Form submissions
    $('#addSparepartsInForm').submit(handleAddSparepartsIn);
    $('#addSaleForm').submit(handleAddSale);
    $('#addPaymentForm').submit(handleAddPayment);
    $('#addTransferForm').submit(handleAddTransfer);
    
    // Transaction type change handler
    $('#transactionType').change(function() {
        // No special handling needed since balance is calculated on backend
    });
    
    // Generate reports
    $('#generateAgingReportBtn').click(() => generateAgingReport());
    $('#generateSalesReportBtn').click(() => generateSalesReport());
    $('#generatePaymentSummaryBtn').click(() => generatePaymentSummary());
}

// Data loading functions
function loadSparepartsInData() {
    showLoading('sparepartsInTableBody');
    
    const searchTerm = $('#searchSparepartsIn').val() || '';
    const branchFilter = $('#branchFilter').val() || '';
    
    $.ajax({
        url: '../api/spareparts_inventory.php',
        method: 'GET',
        data: {
            action: 'get_spareparts_in',
            page: currentPage,
            limit: itemsPerPage,
            search: searchTerm,
            branch: branchFilter
        },
        dataType: 'json',
        success: function(response) {
            if (response && response.success) {
                sparepartsInData = response.data || [];
                totalItems = parseInt(response.total) || 0;
                renderSparepartsInTable(response.data || []);
                updatePaginationControls();
            } else {
                showError('Failed to load spareparts in data: ' + (response.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            console.log('Spareparts In AJAX Error:', error);
            showError('Error loading spareparts in: ' + error);
        }
    });
}

function loadSalesData() {
    showLoading('salesTableBody');
    
    const searchTerm = $('#searchSales').val() || '';
    const branchFilter = $('#salesBranchFilter').val() || '';
    const startDate = $('#salesStartDate').val() || '';
    const endDate = $('#salesEndDate').val() || '';
    
    $.ajax({
        url: '../api/spareparts_inventory.php',
        method: 'GET',
        data: {
            action: 'get_sales',
            page: currentPage,
            limit: itemsPerPage,
            search: searchTerm,
            branch: branchFilter,
            start_date: startDate,
            end_date: endDate
        },
        dataType: 'json',
        success: function(response) {
            if (response && response.success) {
                salesData = response.data || [];
                totalItems = parseInt(response.total) || 0;
                renderSalesTable(response.data || []);
                updatePaginationControls();
            } else {
                showError('Failed to load sales data: ' + (response.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            console.log('Sales AJAX Error:', error);
            showError('Error loading sales: ' + error);
        }
    });
}

function loadPaymentsData() {
    showLoading('paymentsTableBody');
    
    const searchTerm = $('#searchPayments').val() || '';
    const branchFilter = $('#paymentsBranchFilter').val() || '';
    const startDate = $('#paymentsStartDate').val() || '';
    const endDate = $('#paymentsEndDate').val() || '';
    
    $.ajax({
        url: '../api/spareparts_inventory.php',
        method: 'GET',
        data: {
            action: 'get_payments',
            page: currentPage,
            limit: itemsPerPage,
            search: searchTerm,
            branch: branchFilter,
            start_date: startDate,
            end_date: endDate
        },
        dataType: 'json',
        success: function(response) {
            if (response && response.success) {
                paymentsData = response.data || [];
                totalItems = parseInt(response.total) || 0;
                renderPaymentsTable(response.data || []);
                updatePaginationControls();
            } else {
                showError('Failed to load payments data: ' + (response.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            console.log('Payments AJAX Error:', error);
            showError('Error loading payments: ' + error);
        }
    });
}

function loadTransfersData() {
    showLoading('transfersTableBody');
    
    const searchTerm = $('#searchTransfers').val() || '';
    const branchFilter = $('#transfersBranchFilter').val() || '';
    const startDate = $('#transfersStartDate').val() || '';
    const endDate = $('#transfersEndDate').val() || '';
    
    $.ajax({
        url: '../api/spareparts_inventory.php',
        method: 'GET',
        data: {
            action: 'get_transfers',
            page: currentPage,
            limit: itemsPerPage,
            search: searchTerm,
            branch: branchFilter,
            start_date: startDate,
            end_date: endDate
        },
        dataType: 'json',
        success: function(response) {
            if (response && response.success) {
                transfersData = response.data || [];
                totalItems = parseInt(response.total) || 0;
                renderTransfersTable(response.data || []);
                updatePaginationControls();
            } else {
                showError('Failed to load transfers data: ' + (response.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            console.log('Transfers AJAX Error:', error);
            showError('Error loading transfers: ' + error);
        }
    });
}

function loadCurrentTabData() {
    const activeTab = $('.nav-link.active').data('bs-target');
    
    switch(activeTab) {
        case '#spareparts-in':
            loadSparepartsInData();
            break;
        case '#sales':
            loadSalesData();
            break;
        case '#payments':
            loadPaymentsData();
            break;
        case '#transfers':
            loadTransfersData();
            break;
    }
}

function loadDashboardStats() {
    $.ajax({
        url: '../api/spareparts_inventory.php',
        method: 'GET',
        data: {
            action: 'get_dashboard_stats',
            period: 'current_month'
        },
        dataType: 'json',
        success: function(response) {
            if (response && response.success) {
                renderDashboardStats(response.data);
            }
        },
        error: function(xhr, status, error) {
            console.log('Dashboard Stats Error:', error);
        }
    });
}

// Render functions
function renderSparepartsInTable(data) {
    const tbody = $('#sparepartsInTableBody');
    tbody.empty();
    
    if (data.length === 0) {
        tbody.append('<tr><td colspan="7" class="text-center">No spareparts in records found</td></tr>');
        return;
    }
    
    data.forEach(item => {
        const row = `
            <tr>
                <td>${escapeHtml(item.part_no)}</td>
                <td>${item.quantity}</td>
                <td>₱${parseFloat(item.cost).toFixed(2)}</td>
                <td>${formatDate(item.date_received)}</td>
                <td>${escapeHtml(item.invoice_no)}</td>
                <td>${escapeHtml(item.branch)}</td>
                <td>
                    <button class="btn btn-sm btn-outline-info view-spareparts-in" data-id="${item.id}">
                        <i class="bi bi-eye"></i>
                    </button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

function renderSalesTable(data) {
    const tbody = $('#salesTableBody');
    tbody.empty();
    
    if (data.length === 0) {
        tbody.append('<tr><td colspan="9" class="text-center">No sales records found</td></tr>');
        return;
    }
    
    data.forEach(sale => {
        const balanceDisplay = sale.balance > 0 ? 
            `<span class="text-danger fw-bold">₱${parseFloat(sale.balance).toFixed(2)}</span>` : 
            '<span class="text-success">Paid</span>';
            
        const row = `
            <tr>
                <td>${escapeHtml(sale.part_no)}</td>
                <td>${formatDate(sale.sale_date)}</td>
                <td><span class="badge ${sale.transaction_type === 'cash' ? 'bg-success' : 'bg-warning'}">${sale.transaction_type}</span></td>
                <td>${sale.quantity}</td>
                <td>₱${parseFloat(sale.amount).toFixed(2)}</td>
                <td>${escapeHtml(sale.or_number)}</td>
                <td>${escapeHtml(sale.customer_name)}</td>
                <td>${balanceDisplay}</td>
                <td>
                    <button class="btn btn-sm btn-outline-info view-sale" data-id="${sale.id}">
                        <i class="bi bi-eye"></i>
                    </button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

function renderPaymentsTable(data) {
    const tbody = $('#paymentsTableBody');
    tbody.empty();
    
    if (data.length === 0) {
        tbody.append('<tr><td colspan="5" class="text-center">No payment records found</td></tr>');
        return;
    }
    
    data.forEach(payment => {
        const row = `
            <tr>
                <td>${formatDate(payment.payment_date)}</td>
                <td>${escapeHtml(payment.customer_name)}</td>
                <td>₱${parseFloat(payment.amount).toFixed(2)}</td>
                <td>${escapeHtml(payment.branch)}</td>
                <td>
                    <button class="btn btn-sm btn-outline-info view-payment" data-id="${payment.id}">
                        <i class="bi bi-eye"></i>
                    </button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

function renderTransfersTable(data) {
    const tbody = $('#transfersTableBody');
    tbody.empty();
    
    if (data.length === 0) {
        tbody.append('<tr><td colspan="7" class="text-center">No transfer records found</td></tr>');
        return;
    }
    
    data.forEach(transfer => {
        const totalCost = parseFloat(transfer.quantity * transfer.cost).toFixed(2);
        
        const row = `
            <tr>
                <td>${formatDate(transfer.transfer_date)}</td>
                <td>${escapeHtml(transfer.part_no)}</td>
                <td>${transfer.quantity}</td>
                <td>₱${parseFloat(transfer.cost).toFixed(2)}</td>
                <td>₱${totalCost}</td>
                <td>${escapeHtml(transfer.from_branch)} → ${escapeHtml(transfer.to_branch)}</td>
                <td>
                    <button class="btn btn-sm btn-outline-info view-transfer" data-id="${transfer.id}">
                        <i class="bi bi-eye"></i>
                    </button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

function renderDashboardStats(data) {
    // Update sales stats
    $('#totalSales').text(data.sales.total_sales || 0);
    $('#totalSalesAmount').text('₱' + parseFloat(data.sales.total_amount || 0).toLocaleString());
    $('#cashSales').text((data.sales.cash_sales.count || 0) + ' (₱' + parseFloat(data.sales.cash_sales.amount || 0).toLocaleString() + ')');
    $('#installmentSales').text((data.sales.installment_sales.count || 0) + ' (₱' + parseFloat(data.sales.installment_sales.amount || 0).toLocaleString() + ')');
    
    // Update payment stats
    $('#totalPayments').text(data.payments.total_payments || 0);
    $('#totalPaymentsAmount').text('₱' + parseFloat(data.payments.total_amount || 0).toLocaleString());
    
    // Update outstanding balances
    $('#outstandingCustomers').text(data.outstanding.total_customers || 0);
    $('#outstandingBalance').text('₱' + parseFloat(data.outstanding.total_balance || 0).toLocaleString());
    
    // Update recent transfers
    $('#recentTransfers').text(data.transfers.recent_count || 0);
    
    // Render recent activities
    if (data.recent_activities) {
        renderRecentActivities(data.recent_activities);
    }
}

function renderRecentActivities(activities) {
    const container = $('#recentActivities');
    container.empty();
    
    if (activities.length === 0) {
        container.append('<p class="text-muted">No recent activities</p>');
        return;
    }
    
    activities.forEach(activity => {
        const iconClass = activity.type === 'sale' ? 'bi-cart-plus text-success' :
                         activity.type === 'payment' ? 'bi-cash text-primary' :
                         'bi-arrow-left-right text-warning';
        
        const item = `
            <div class="d-flex align-items-center mb-3">
                <div class="me-3">
                    <i class="bi ${iconClass}"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-medium">${escapeHtml(activity.description)}</div>
                    <small class="text-muted">${formatDate(activity.date)} - ${activity.type.toUpperCase()}</small>
                </div>
                <div class="text-end">
                    <div class="fw-medium">₱${parseFloat(activity.amount).toLocaleString()}</div>
                    <small class="text-muted">${escapeHtml(activity.branch)}</small>
                </div>
            </div>
        `;
        container.append(item);
    });
}

// Form handlers
function handleAddSparepartsIn(e) {
    e.preventDefault();
    
    const formData = {
        action: 'add_spareparts_in',
        part_no: $('#partNo').val(),
        quantity: $('#quantity').val(),
        cost: $('#cost').val(),
        date_received: $('#dateReceived').val(),
        invoice_no: $('#invoiceNo').val(),
        branch: $('#branch').val() || 'MAIN',
        notes: $('#notes').val() || ''
    };
    
    $.ajax({
        url: '../api/spareparts_inventory.php',
        method: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response && response.success) {
                showSuccess('Spareparts received successfully!');
                $('#addSparepartsModal').modal('hide');
                $('#addSparepartsInForm')[0].reset();
                loadSparepartsInData();
                loadDashboardStats();
            } else {
                showError('Failed to receive spareparts: ' + (response.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            console.log('Add Spareparts Error:', error);
            console.log('Response:', xhr.responseText);
            showError('Error adding spareparts: ' + error);
        }
    });
}

function handleAddSale(e) {
    e.preventDefault();
    
    const formData = {
        action: 'add_sale',
        part_no: $('#salePartNo').val(),
        sale_date: $('#saleDate').val(),
        transaction_type: $('#transactionType').val(),
        quantity: $('#saleQuantity').val(),
        amount: $('#saleAmount').val(),
        or_number: $('#orNumber').val(),
        customer_name: $('#customerName').val(),
        branch: $('#saleBranch').val() || 'MAIN'
    };
    
    $.ajax({
        url: '../api/spareparts_inventory.php',
        method: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response && response.success) {
                showSuccess('Sale recorded successfully!');
                $('#addSaleModal').modal('hide');
                $('#addSaleForm')[0].reset();
                loadSalesData();
                loadDashboardStats();
            } else {
                showError('Failed to record sale: ' + (response.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            console.log('Add Sale Error:', error);
            showError('Error recording sale: ' + error);
        }
    });
}

function handleAddPayment(e) {
    e.preventDefault();
    
    const formData = {
        action: 'add_payment',
        payment_date: $('#paymentDate').val(),
        customer_name: $('#paymentCustomerName').val(),
        amount: $('#paymentAmount').val(),
        branch: $('#paymentBranch').val() || 'MAIN',
        notes: $('#paymentNotes').val() || ''
    };
    
    $.ajax({
        url: '../api/spareparts_inventory.php',
        method: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response && response.success) {
                showSuccess('Payment recorded successfully!');
                $('#addPaymentModal').modal('hide');
                $('#addPaymentForm')[0].reset();
                loadPaymentsData();
                loadSalesData(); // Refresh to show updated balances
                loadDashboardStats();
            } else {
                showError('Failed to record payment: ' + (response.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            console.log('Add Payment Error:', error);
            showError('Error recording payment: ' + error);
        }
    });
}

function handleAddTransfer(e) {
    e.preventDefault();
    
    const formData = {
        action: 'add_transfer',
        transfer_date: $('#transferDate').val(),
        part_no: $('#transferPartNo').val(),
        quantity: $('#transferQuantity').val(),
        cost: $('#transferCost').val(),
        from_branch: $('#fromBranch').val() || 'MAIN',
        to_branch: $('#toBranch').val(),
        notes: $('#transferNotes').val() || ''
    };
    
    $.ajax({
        url: '../api/spareparts_inventory.php',
        method: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response && response.success) {
                showSuccess('Transfer completed successfully!');
                $('#addTransferModal').modal('hide');
                $('#addTransferForm')[0].reset();
                loadTransfersData();
                loadDashboardStats();
            } else {
                showError('Failed to process transfer: ' + (response.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            console.log('Add Transfer Error:', error);
            showError('Error processing transfer: ' + error);
        }
    });
}

// Report generation functions
function generateAgingReport() {
    const month = $('#agingReportMonth').val() || new Date().toISOString().slice(0, 7);
    const branch = $('#agingReportBranch').val() || '';
    
    $.ajax({
        url: '../api/spareparts_inventory.php',
        method: 'GET',
        data: {
            action: 'get_aging_report',
            month: month,
            branch: branch
        },
        dataType: 'json',
        success: function(response) {
            if (response && response.success) {
                renderAgingReport(response.data, response.summary);
                $('#agingReportModal').modal('show');
            } else {
                showError('Failed to generate aging report: ' + (response.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            console.log('Aging Report Error:', error);
            showError('Error generating aging report: ' + error);
        }
    });
}

function generateSalesReport() {
    const reportType = $('#salesReportType').val() || 'monthly';
    const period = $('#salesReportPeriod').val() || '';
    const branch = $('#salesReportBranch').val() || '';
    
    $.ajax({
        url: '../api/spareparts_inventory.php',
        method: 'GET',
        data: {
            action: 'get_sales_report',
            report_type: reportType,
            period: period,
            branch: branch
        },
        dataType: 'json',
        success: function(response) {
            if (response && response.success) {
                renderSalesReport(response.data, response.summary);
                $('#salesReportModal').modal('show');
            } else {
                showError('Failed to generate sales report: ' + (response.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            console.log('Sales Report Error:', error);
            showError('Error generating sales report: ' + error);
        }
    });
}

function generatePaymentSummary() {
    const reportType = $('#paymentSummaryType').val() || 'monthly';
    const period = $('#paymentSummaryPeriod').val() || '';
    const branch = $('#paymentSummaryBranch').val() || '';
    
    $.ajax({
        url: '../api/spareparts_inventory.php',
        method: 'GET',
        data: {
            action: 'get_payment_summary',
            report_type: reportType,
            period: period,
            branch: branch
        },
        dataType: 'json',
        success: function(response) {
            if (response && response.success) {
                renderPaymentSummary(response.data, response.summary);
                $('#paymentSummaryModal').modal('show');
            } else {
                showError('Failed to generate payment summary: ' + (response.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            console.log('Payment Summary Error:', error);
            showError('Error generating payment summary: ' + error);
        }
    });
}

// Report rendering functions
function renderAgingReport(data, summary) {
    const tbody = $('#agingReportTable tbody');
    tbody.empty();
    
    data.forEach(item => {
        const row = `
            <tr>
                <td>${item.or_number}</td>
                <td>${escapeHtml(item.customer_name)}</td>
                <td>${formatDate(item.sale_date)}</td>
                <td>₱${parseFloat(item.original_amount).toFixed(2)}</td>
                <td>₱${parseFloat(item.total_payments || 0).toFixed(2)}</td>
                <td class="text-danger fw-bold">₱${parseFloat(item.current_balance).toFixed(2)}</td>
                <td>${item.days_outstanding} days</td>
                <td><span class="badge ${getAgingBadgeClass(item.aging_category)}">${item.aging_category}</span></td>
            </tr>
        `;
        tbody.append(row);
    });
    
    // Update summary
    $('#agingTotalOutstanding').text('₱' + parseFloat(summary.total_outstanding).toLocaleString());
    $('#agingTotalCustomers').text(summary.total_customers);
}

function renderSalesReport(data, summary) {
    const tbody = $('#salesReportTable tbody');
    tbody.empty();
    
    data.forEach(sale => {
        const row = `
            <tr>
                <td>${escapeHtml(sale.part_no)}</td>
                <td>${formatDate(sale.sale_date)}</td>
                <td><span class="badge ${sale.transaction_type === 'cash' ? 'bg-success' : 'bg-warning'}">${sale.transaction_type}</span></td>
                <td>${sale.quantity}</td>
                <td>₱${parseFloat(sale.amount).toFixed(2)}</td>
                <td>${escapeHtml(sale.or_number)}</td>
                <td>${escapeHtml(sale.customer_name)}</td>
                <td>₱${parseFloat(sale.balance).toFixed(2)}</td>
            </tr>
        `;
        tbody.append(row);
    });
    
    // Update summary
    $('#salesReportTotalSales').text(summary.total_sales);
    $('#salesReportTotalAmount').text('₱' + parseFloat(summary.total_amount).toLocaleString());
    $('#salesReportCashSales').text(summary.cash_sales.count + ' (₱' + parseFloat(summary.cash_sales.amount).toLocaleString() + ')');
    $('#salesReportInstallmentSales').text(summary.installment_sales.count + ' (₱' + parseFloat(summary.installment_sales.amount).toLocaleString() + ')');
}

function renderPaymentSummary(data, summary) {
    const tbody = $('#paymentSummaryTable tbody');
    tbody.empty();
    
    data.forEach(payment => {
        const row = `
            <tr>
                <td>${formatDate(payment.payment_date)}</td>
                <td>${escapeHtml(payment.customer_name)}</td>
                <td>₱${parseFloat(payment.amount).toFixed(2)}</td>
                <td>${escapeHtml(payment.branch)}</td>
            </tr>
        `;
        tbody.append(row);
    });
    
    // Update summary
    $('#paymentSummaryTotalPayments').text(summary.total_payments);
    $('#paymentSummaryTotalAmount').text('₱' + parseFloat(summary.total_amount).toLocaleString());
}

// Search functions
function searchSparepartsIn(term) {
    $('#searchSparepartsIn').val(term);
    currentPage = 1;
    loadSparepartsInData();
}

function searchSales(term) {
    $('#searchSales').val(term);
    currentPage = 1;
    loadSalesData();
}

function searchPayments(term) {
    $('#searchPayments').val(term);
    currentPage = 1;
    loadPaymentsData();
}

function searchTransfers(term) {
    $('#searchTransfers').val(term);
    currentPage = 1;
    loadTransfersData();
}

function updatePaginationControls() {
    const totalPages = Math.ceil(totalItems / itemsPerPage);
    
    $('#prevPage').toggleClass('disabled', currentPage === 1);
    $('#nextPage').toggleClass('disabled', currentPage === totalPages);
    
    // Update pagination info
    $('#paginationInfo').text(`Page ${currentPage} of ${totalPages} (${totalItems} items)`);
    
    // Update pagination numbers
    let paginationHtml = '';
    
    if (totalPages > 1) {
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, startPage + 4);
        
        for (let i = startPage; i <= endPage; i++) {
            paginationHtml += `
                <li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `;
        }
        
        $('#paginationControls').html(`
            <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage - 1}">
                    <i class="fas fa-chevron-left me-1"></i> Previous
                </a>
            </li>
            ${paginationHtml}
            <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage + 1}">
                    Next <i class="fas fa-chevron-right ms-1"></i>
                </a>
            </li>
        `);
        
        // Add click event to page links
        $('.page-link[data-page]').click(function(e) {
            e.preventDefault();
            const newPage = parseInt($(this).data('page'));
            if (newPage !== currentPage && newPage >= 1 && newPage <= totalPages) {
                currentPage = newPage;
                loadCurrentTabData();
            }
        });
    }
}

function getAgingBadgeClass(category) {
    switch (category) {
        case '0-30 days': return 'bg-success';
        case '31-60 days': return 'bg-warning';
        case '61-90 days': return 'bg-danger';
        case 'Over 90 days': return 'bg-dark';
        default: return 'bg-secondary';
    }
}

// Export functions
function exportToExcel(tableId, filename) {
    if (typeof XLSX !== 'undefined') {
        const table = document.getElementById(tableId);
        const ws = XLSX.utils.table_to_sheet(table);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Sheet1');
        XLSX.writeFile(wb, filename + '.xlsx');
    } else {
        showWarning('Excel export library not loaded');
    }
}

function exportToPDF(elementId, filename) {
    if (typeof jsPDF !== 'undefined' && typeof html2canvas !== 'undefined') {
        const element = document.getElementById(elementId);
        html2canvas(element).then(canvas => {
            const imgData = canvas.toDataURL('image/png');
            const pdf = new jsPDF();
            const imgWidth = 210;
            const pageHeight = 295;
            const imgHeight = (canvas.height * imgWidth) / canvas.width;
            let heightLeft = imgHeight;
            
            let position = 0;
            
            pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;
            
            while (heightLeft >= 0) {
                position = heightLeft - imgHeight;
                pdf.addPage();
                pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;
            }
            
            pdf.save(filename + '.pdf');
        });
    } else {
        showWarning('PDF export libraries not loaded');
    }
}

function printReport(elementId) {
    const printWindow = window.open('', '_blank');
    const element = document.getElementById(elementId);
    
    printWindow.document.write(`
        <html>
            <head>
                <title>Print Report</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
                <style>
                    @media print {
                        .no-print { display: none !important; }
                        body { font-size: 12px; }
                        table { font-size: 11px; }
                        .table th, .table td { padding: 0.25rem; }
                    }
                    body { margin: 20px; }
                    .table { margin-bottom: 1rem; }
                </style>
            </head>
            <body>
                <div class="container-fluid">
                    <div class="text-center mb-4">
                        <h3>SMDI Spareparts Report</h3>
                        <p>Generated on: ${new Date().toLocaleDateString()}</p>
                    </div>
                    ${element.innerHTML}
                </div>
            </body>
        </html>
    `);
    
    printWindow.document.close();
    printWindow.focus();
    
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 250);
}

// Auto-refresh functionality
let autoRefreshInterval;

function toggleAutoRefresh() {
    const isEnabled = $('#autoRefreshToggle').prop('checked');
    
    if (isEnabled) {
        const interval = parseInt($('#autoRefreshInterval').val()) * 1000; // Convert to milliseconds
        autoRefreshInterval = setInterval(() => {
            loadCurrentTabData();
            loadDashboardStats();
        }, interval);
        showSuccess('Auto-refresh enabled');
    } else {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
            autoRefreshInterval = null;
        }
        showSuccess('Auto-refresh disabled');
    }
}

// Keyboard shortcuts
$(document).keydown(function(e) {
    // Ctrl+F for search
    if (e.ctrlKey && e.keyCode === 70) {
        e.preventDefault();
        const activeTab = $('.nav-link.active').data('bs-target');
        switch(activeTab) {
            case '#spareparts-in':
                $('#searchSparepartsIn').focus();
                break;
            case '#sales':
                $('#searchSales').focus();
                break;
            case '#payments':
                $('#searchPayments').focus();
                break;
            case '#transfers':
                $('#searchTransfers').focus();
                break;
        }
    }
    
    // Ctrl+N for new entry based on active tab
    if (e.ctrlKey && e.keyCode === 78) {
        e.preventDefault();
        const activeTab = $('.nav-link.active').data('bs-target');
        switch(activeTab) {
            case '#spareparts-in':
                $('#addSparepartsModal').modal('show');
                break;
            case '#sales':
                $('#addSaleModal').modal('show');
                break;
            case '#payments':
                $('#addPaymentModal').modal('show');
                break;
            case '#transfers':
                $('#addTransferModal').modal('show');
                break;
        }
    }
    
    // Escape to close modals
    if (e.keyCode === 27) {
        $('.modal').modal('hide');
    }
});

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (form.checkValidity() === false) {
        form.classList.add('was-validated');
        return false;
    }
    return true;
}

// Add form validation to all forms
$('form').submit(function(e) {
    if (!validateForm(this.id)) {
        e.preventDefault();
        e.stopPropagation();
        showError('Please fill in all required fields correctly');
    }
});

// Customer and part number suggestions
function setupAutoComplete() {
    // Customer name autocomplete for payments
    $('#paymentCustomerName').on('input', function() {
        const customerName = $(this).val();
        if (customerName.length >= 2) {
            searchCustomersWithBalance(customerName);
        } else {
            $('#customerSuggestions').hide();
        }
    });
    
    // Part number autocomplete for sales and transfers
    $('#salePartNo, #transferPartNo').on('input', function() {
        const partNo = $(this).val();
        const targetSuggestions = $(this).attr('id') === 'salePartNo' ? '#salePartSuggestions' : '#transferPartSuggestions';
        
        if (partNo.length >= 2) {
            searchPartNumbers(partNo, targetSuggestions);
        } else {
            $(targetSuggestions).hide();
        }
    });
}

function searchCustomersWithBalance(customerName) {
    $.ajax({
        url: '../api/spareparts_inventory.php',
        method: 'GET',
        data: {
            action: 'get_sales',
            search: customerName,
            limit: 10
        },
        dataType: 'json',
        success: function(response) {
            if (response && response.success) {
                const customersWithBalance = response.data.filter(sale => sale.balance > 0);
                renderCustomerSuggestions(customersWithBalance);
            }
        },
        error: function(xhr, status, error) {
            console.log('Customer search error:', error);
        }
    });
}

function searchPartNumbers(partNo, targetElement) {
    $.ajax({
        url: '../api/spareparts_inventory.php',
        method: 'GET',
        data: {
            action: 'get_spareparts_in',
            search: partNo,
            limit: 10
        },
        dataType: 'json',
        success: function(response) {
            if (response && response.success) {
                renderPartSuggestions(response.data, targetElement);
            }
        },
        error: function(xhr, status, error) {
            console.log('Part search error:', error);
        }
    });
}

function renderCustomerSuggestions(customers) {
    const container = $('#customerSuggestions');
    container.empty();
    
    if (customers.length === 0) {
        container.hide();
        return;
    }
    
    // Group by customer name and sum balances
    const customerMap = {};
    customers.forEach(sale => {
        if (!customerMap[sale.customer_name]) {
            customerMap[sale.customer_name] = {
                name: sale.customer_name,
                totalBalance: 0,
                salesCount: 0
            };
        }
        customerMap[sale.customer_name].totalBalance += parseFloat(sale.balance);
        customerMap[sale.customer_name].salesCount++;
    });
    
    Object.values(customerMap).forEach(customer => {
        const suggestion = `
            <div class="suggestion-item p-2 border-bottom cursor-pointer" data-customer-name="${customer.name}" data-balance="${customer.totalBalance}">
                <div class="fw-medium">${escapeHtml(customer.name)}</div>
                <div class="text-danger small">Outstanding: ₱${parseFloat(customer.totalBalance).toFixed(2)} (${customer.salesCount} sales)</div>
            </div>
        `;
        container.append(suggestion);
    });
    
    container.show();
    
    // Add click handlers
    $('.suggestion-item').click(function() {
        const customerName = $(this).data('customer-name');
        const balance = $(this).data('balance');
        
        $('#paymentCustomerName').val(customerName);
        $('#paymentAmount').attr('max', balance);
        container.hide();
    });
}

function renderPartSuggestions(parts, targetElement) {
    const container = $(targetElement);
    container.empty();
    
    if (parts.length === 0) {
        container.hide();
        return;
    }
    
    // Get unique part numbers
    const uniqueParts = {};
    parts.forEach(part => {
        if (!uniqueParts[part.part_no]) {
            uniqueParts[part.part_no] = {
                part_no: part.part_no,
                latest_cost: part.cost,
                total_quantity: 0
            };
        }
        uniqueParts[part.part_no].total_quantity += parseInt(part.quantity);
        // Keep the latest cost (assuming data is ordered by date)
        uniqueParts[part.part_no].latest_cost = part.cost;
    });
    
    Object.values(uniqueParts).forEach(part => {
        const suggestion = `
            <div class="suggestion-item p-2 border-bottom cursor-pointer" data-part-no="${part.part_no}" data-cost="${part.latest_cost}">
                <div class="fw-medium">${escapeHtml(part.part_no)}</div>
                <div class="text-muted small">Total received: ${part.total_quantity} pcs</div>
                <div class="text-success small">Latest cost: ₱${parseFloat(part.latest_cost).toFixed(2)}</div>
            </div>
        `;
        container.append(suggestion);
    });
    
    container.show();
    
    // Add click handlers
    $('.suggestion-item').click(function() {
        const partNo = $(this).data('part-no');
        const cost = $(this).data('cost');
        
        if (targetElement === '#salePartSuggestions') {
            $('#salePartNo').val(partNo);
        } else if (targetElement === '#transferPartSuggestions') {
            $('#transferPartNo').val(partNo);
            $('#transferCost').val(cost);
        }
        
        container.hide();
    });
}

// Utility functions
function showLoading(elementId) {
    const colspan = elementId === 'sparepartsInTableBody' ? '7' :
                   elementId === 'salesTableBody' ? '9' :
                   elementId === 'paymentsTableBody' ? '5' :
                   elementId === 'transfersTableBody' ? '7' : '5';
    
    $(`#${elementId}`).html(`<tr><td colspan="${colspan}" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>`);
}

function showSuccess(message) {
    if ($('#successToast').length) {
        $('#successToastMessage').text(message);
        const toast = new bootstrap.Toast(document.getElementById('successToast'));
        toast.show();
    } else {
        // Fallback alert
        alert('Success: ' + message);
    }
}

function showError(message) {
    if ($('#errorToast').length) {
        $('#errorToastMessage').text(message);
        const toast = new bootstrap.Toast(document.getElementById('errorToast'));
        toast.show();
    } else {
        // Fallback alert
        alert('Error: ' + message);
    }
}

function showWarning(message) {
    if ($('#warningToast').length) {
        $('#warningToastMessage').text(message);
        const toast = new bootstrap.Toast(document.getElementById('warningToast'));
        toast.show();
    } else {
        // Fallback alert
        alert('Warning: ' + message);
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
}

function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function formatCurrency(amount) {
    return '₱' + parseFloat(amount || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function formatNumber(number) {
    return parseInt(number || 0).toLocaleString();
}

// Initialize tooltips and popovers
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Date range picker functionality
function initializeDateRangePickers() {
    // Set default date ranges
    const today = new Date();
    const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    const lastDayOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    
    const formatDate = (date) => date.toISOString().split('T')[0];
    
    // Set default values for date filters
    $('#salesStartDate, #paymentsStartDate, #transfersStartDate').val(formatDate(firstDayOfMonth));
    $('#salesEndDate, #paymentsEndDate, #transfersEndDate').val(formatDate(lastDayOfMonth));
    
    // Add change event listeners for date filters
    $('#salesStartDate, #salesEndDate').change(function() {
        loadSalesData();
    });
    
    $('#paymentsStartDate, #paymentsEndDate').change(function() {
        loadPaymentsData();
    });
    
    $('#transfersStartDate, #transfersEndDate').change(function() {
        loadTransfersData();
    });
}

// Quick date filter buttons
function setQuickDateFilter(period, targetPrefix) {
    const today = new Date();
    let startDate, endDate;
    
    switch(period) {
        case 'today':
            startDate = endDate = today;
            break;
        case 'yesterday':
            startDate = endDate = new Date(today.getTime() - 24 * 60 * 60 * 1000);
            break;
        case 'this_week':
            startDate = new Date(today.setDate(today.getDate() - today.getDay()));
            endDate = new Date(today.setDate(today.getDate() - today.getDay() + 6));
            break;
        case 'this_month':
            startDate = new Date(today.getFullYear(), today.getMonth(), 1);
            endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            break;
        case 'last_month':
            startDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            endDate = new Date(today.getFullYear(), today.getMonth(), 0);
            break;
        default:
            return;
    }
    
    const formatDate = (date) => date.toISOString().split('T')[0];
    
    $(`#${targetPrefix}StartDate`).val(formatDate(startDate));
    $(`#${targetPrefix}EndDate`).val(formatDate(endDate));
    
    // Trigger data reload based on target
    switch(targetPrefix) {
        case 'sales':
            loadSalesData();
            break;
        case 'payments':
            loadPaymentsData();
            break;
        case 'transfers':
            loadTransfersData();
            break;
    }
}

// Advanced filtering
function showAdvancedFilters(tabName) {
    $(`#${tabName}AdvancedFilters`).toggle();
}

function clearFilters(tabName) {
    // Clear search input
    $(`#search${tabName.charAt(0).toUpperCase() + tabName.slice(1)}`).val('');
    
    // Clear date filters
    $(`#${tabName}StartDate, #${tabName}EndDate`).val('');
    
    // Clear branch filter
    $(`#${tabName}BranchFilter`).val('');
    
    // Reload data
    switch(tabName) {
        case 'sales':
            loadSalesData();
            break;
        case 'payments':
            loadPaymentsData();
            break;
        case 'transfers':
            loadTransfersData();
            break;
        case 'sparepartsIn':
            loadSparepartsInData();
            break;
    }
}

// Data refresh functionality
function refreshCurrentTab() {
    const activeTab = $('.nav-link.active').data('bs-target');
    
    switch(activeTab) {
        case '#spareparts-in':
            loadSparepartsInData();
            break;
        case '#sales':
            loadSalesData();
            break;
        case '#payments':
            loadPaymentsData();
            break;
        case '#transfers':
            loadTransfersData();
            break;
    }
    
    loadDashboardStats();
    showSuccess('Data refreshed successfully');
}

// Print specific reports
function printAgingReport() {
    printReport('agingReportContent');
}

function printSalesReport() {
    printReport('salesReportContent');
}

function printPaymentSummary() {
    printReport('paymentSummaryContent');
}

// Export specific reports
function exportAgingReportToExcel() {
    exportToExcel('agingReportTable', 'Aging_Report_' + new Date().toISOString().slice(0, 10));
}

function exportSalesReportToExcel() {
    exportToExcel('salesReportTable', 'Sales_Report_' + new Date().toISOString().slice(0, 10));
}

function exportPaymentSummaryToExcel() {
    exportToExcel('paymentSummaryTable', 'Payment_Summary_' + new Date().toISOString().slice(0, 10));
}

// Handle window resize for responsive tables
$(window).resize(function() {
    // Adjust table layouts if needed
    $('.table-responsive').each(function() {
        // Custom responsive table handling can be added here
    });
});

// Clean up intervals when page unloads
$(window).on('beforeunload', function() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
});

// Hide suggestion dropdowns when clicking outside
$(document).click(function(e) {
    if (!$(e.target).closest('.suggestion-container').length) {
        $('.suggestion-dropdown').hide();
    }
});

// Form reset handlers
function resetForm(formId) {
    $(`#${formId}`)[0].reset();
    $(`#${formId}`).removeClass('was-validated');
    
    // Reset any custom fields
    if (formId === 'addSaleForm') {
        $('#installmentFields').hide();
    }
}

// Modal event handlers
$('.modal').on('hidden.bs.modal', function() {
    const formId = $(this).find('form').attr('id');
    if (formId) {
        resetForm(formId);
    }
    
    // Hide any suggestion dropdowns
    $('.suggestion-dropdown').hide();
});

// Initialize everything when DOM is ready
$(document).ready(function() {
    console.log('SMDI Spareparts Inventory System initialized');
    
    // Initialize components
    initializeTooltips();
    initializeDateRangePickers();
    setupAutoComplete();
    
    // Set up periodic refresh for dashboard stats (every 5 minutes)
    setInterval(loadDashboardStats, 300000);
    
    // Initialize date pickers with current date
    const today = new Date().toISOString().split('T')[0];
    $('input[type="date"]').each(function() {
        if (!$(this).val() && $(this).attr('id').includes('Date') && !$(this).attr('id').includes('Start') && !$(this).attr('id').includes('End')) {
            $(this).val(today);
        }
    });
    
    // Add loading states to buttons
    $('button[type="submit"]').click(function() {
        const $btn = $(this);
        const originalText = $btn.html();
        
        $btn.html('<span class="spinner-border spinner-border-sm me-2" role="status"></span>Processing...');
        $btn.prop('disabled', true);
        
        // Re-enable button after 3 seconds (fallback)
        setTimeout(() => {
            $btn.html(originalText);
            $btn.prop('disabled', false);
        }, 3000);
    });
    
    // Handle form submission success/error to re-enable buttons
    $('form').on('ajax:complete', function() {
        const $btn = $(this).find('button[type="submit"]');
        const originalText = $btn.data('original-text') || 'Submit';
        
        $btn.html(originalText);
        $btn.prop('disabled', false);
    });
    
    // Add confirmation for sensitive operations
    $('.btn-danger').click(function(e) {
        if (!confirm('Are you sure you want to perform this action?')) {
            e.preventDefault();
            return false;
        }
    });
    
    // Auto-save form data to localStorage (optional)
    $('input, textarea, select').on('change', function() {
        const formId = $(this).closest('form').attr('id');
        const fieldId = $(this).attr('id');
        
        if (formId && fieldId) {
            localStorage.setItem(`${formId}_${fieldId}`, $(this).val());
        }
    });
    
    // Restore form data from localStorage (optional)
    $('input, textarea, select').each(function() {
        const formId = $(this).closest('form').attr('id');
        const fieldId = $(this).attr('id');
        
        if (formId && fieldId) {
            const savedValue = localStorage.getItem(`${formId}_${fieldId}`);
            if (savedValue && !$(this).val()) {
                $(this).val(savedValue);
            }
        }
    });
    
    // Clear localStorage on successful form submission
    $('form').on('ajax:success', function() {
        const formId = $(this).attr('id');
        
        $(this).find('input, textarea, select').each(function() {
            const fieldId = $(this).attr('id');
            if (fieldId) {
                localStorage.removeItem(`${formId}_${fieldId}`);
            }
        });
    });
});

// Global error handler for AJAX requests
$(document).ajaxError(function(event, xhr, settings, thrownError) {
    console.error('AJAX Error:', {
        url: settings.url,
        status: xhr.status,
        statusText: xhr.statusText,
        responseText: xhr.responseText,
        thrownError: thrownError
    });
    
    // Don't show error for aborted requests
    if (xhr.statusText !== 'abort') {
        showError('Network error occurred. Please check your connection and try again.');
    }
});

// Global success handler for AJAX requests
$(document).ajaxSuccess(function(event, xhr, settings) {
    console.log('AJAX Success:', settings.url);
});

// Performance monitoring (optional)
if (typeof performance !== 'undefined') {
    window.addEventListener('load', function() {
        setTimeout(function() {
            const perfData = performance.getEntriesByType('navigation')[0];
            console.log('Page Load Performance:', {
                domContentLoaded: perfData.domContentLoadedEventEnd - perfData.domContentLoadedEventStart,
                loadComplete: perfData.loadEventEnd - perfData.loadEventStart,
                totalTime: perfData.loadEventEnd - perfData.fetchStart
            });
        }, 0);
    });
}
