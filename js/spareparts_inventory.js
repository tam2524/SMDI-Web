$(document).ready(function() {
    // --- Global Variables ---
    let inventoryData = [], salesData = [], paymentsData = [], transfersData = [];
    let transferItems = []; // For the new multi-item transfer modal

    // --- Initial Load ---
    loadDashboardStats();
    setupEventListeners();
    if (canViewAllBranches) { loadBranches(); }

    // --- Event Listeners Setup ---
    function setupEventListeners() {
        // Tab switching logic
        $('button[data-bs-toggle="tab"]').on('shown.bs.tab', e => {
            const targetTab = $(e.target).attr('id');
            switch (targetTab) {
                case 'dashboard-tab': loadDashboardStats(); break;
                case 'inventory-tab': loadInventory(); break;
                case 'sales-tab': loadSales(); break;
                case 'payments-tab': loadPayments(); break;
                case 'transfer-tab': loadTransfers(); break;
                case 'reports-tab': initializeReports(); break;
            }
        });

        // Form submissions
        $('#addPartsInForm').submit(e => submitForm(e, 'add_parts_in', '#addPartsInModal', loadInventory));
        $('#sellPartsOutForm').submit(e => submitForm(e, 'sell_parts_out', '#sellPartsOutModal', loadSales));
        $('#recordPaymentForm').submit(e => submitForm(e, 'record_payment', '#recordPaymentModal', loadPayments));
        $('#editPartForm').submit(e => submitForm(e, 'edit_parts', '#editPartModal', loadInventory));
        $('#reportForm').submit(e => { e.preventDefault(); generateReport(); });
        
        // Search functionality
        $('#inventorySearch').on('keyup', e => renderInventory(filterData(inventoryData, $(e.currentTarget).val(), ['part_no', 'description'])));
        $('#salesSearch').on('keyup', e => renderSales(filterData(salesData, $(e.currentTarget).val(), ['part_no', 'customer_name', 'or_number'])));
        $('#paymentsSearch').on('keyup', e => renderPayments(filterData(paymentsData, $(e.currentTarget).val(), ['customer_name', 'or_number'])));
        $('#transfersSearch').on('keyup', e => renderTransfers(filterData(transfersData, $(e.currentTarget).val(), ['part_no', 'from_location', 'to_location'])));

        // --- New Transfer Modal Listeners ---
        $('#transferPartsModal').on('show.bs.modal', initializeTransferModal);
        $('#partSearchInput').on('keyup', handlePartSearch);
        $('#addPartToTransferBtn').on('click', addPartToTransferList);
        $('#transferItemsList').on('click', '.remove-transfer-item', e => {
            const partNo = $(e.currentTarget).data('part');
            removePartFromTransferList(partNo);
        });
        $('#transferPartsForm').on('submit', submitTransfer);
    }
    
    // --- Generic Form Submission ---
    function submitForm(event, action, modalId, successCallback) {
        event.preventDefault();
        const $form = $(event.currentTarget);
        const formData = $form.serialize() + '&action=' + action;

        $.ajax({
            url: '../api/spareparts_inventory.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: response => {
                if (response.success) {
                    showSuccessModal(response.message);
                    $(modalId).modal('hide');
                    $form[0].reset();
                    if (successCallback) successCallback();
                } else { showErrorModal(response.message); }
            },
            error: xhr => showErrorModal('API error: ' + xhr.statusText)
        });
    }

    // --- Data Loading Functions ---
    function loadApiData(endpoint, data, successCallback, tableBodyId) {
        const tableBody = $(`#${tableBodyId}`);
        if (tableBody.length) tableBody.html(`<tr><td colspan="10" class="text-center py-5"><div class='spinner-border text-primary'></div></td></tr>`);
        
        $.ajax({
            url: `../api/spareparts_inventory.php?action=${endpoint}`,
            method: 'GET',
            data: data,
            dataType: 'json',
            success: response => {
                if (response.success) successCallback(response.data);
                else {
                    showErrorModal(response.message);
                    if (tableBody.length) tableBody.html(`<tr><td colspan="10" class="text-center text-muted">Failed to load data.</td></tr>`);
                }
            },
            error: xhr => {
                showErrorModal('API connection failed: ' + xhr.statusText);
                if (tableBody.length) tableBody.html(`<tr><td colspan="10" class="text-center text-danger">API connection failed.</td></tr>`);
            }
        });
    }

    function loadDashboardStats() {
        $('#dashboard-content').addClass('d-none');
        $('#dashboard-loader').removeClass('d-none');
        loadApiData('get_dashboard_stats', { branch: currentBranch }, data => {
            $('#stat-inventory-value').text(`₱${formatCurrency(data.total_value)}`);
            $('#stat-monthly-sales').text(`₱${formatCurrency(data.monthly_sales)}`);
            $('#stat-outstanding-balance').text(`₱${formatCurrency(data.outstanding_balance)}`);
            $('#stat-low-stock').text(data.low_stock_items);
            $('#dashboard-loader').addClass('d-none');
            $('#dashboard-content').removeClass('d-none');
        });
    }
    
    function loadInventory() { loadApiData('get_inventory_list', { branch: currentBranch }, data => { inventoryData = data; renderInventory(data); }, 'inventoryTableBody'); }
    function loadSales() { loadApiData('get_sales_list', { branch: currentBranch }, data => { salesData = data; renderSales(data); }, 'salesTableBody'); }
    function loadPayments() { loadApiData('get_payments_list', { branch: currentBranch }, data => { paymentsData = data; renderPayments(data); }, 'paymentsTableBody'); }
    function loadTransfers() { loadApiData('get_transfers_list', { branch: currentBranch }, data => { transfersData = data; renderTransfers(data); }, 'transfersTableBody'); }
    function loadBranches() { loadApiData('get_all_branches', {}, data => {
        const dropdown = $('#reportBranch');
        dropdown.empty().append(`<option value="All">All Branches</option>`);
        data.forEach(branch => dropdown.append(`<option value="${escapeHtml(branch)}">${escapeHtml(branch)}</option>`));
        dropdown.val(currentBranch);
    });}

    // --- UI Rendering Functions ---
    // renderInventory, renderSales, renderPayments, renderTransfers (These can remain similar to previous versions)
    function renderInventory(data) {
        const tableBody = $('#inventoryTableBody');
        tableBody.empty();
        if (!data || data.length === 0) {
            tableBody.html('<tr><td colspan="8" class="text-center text-muted">No inventory found.</td></tr>'); return;
        }
        data.forEach(part => {
            const totalValue = part.current_stock * part.cost;
            const status = parseInt(part.current_stock) < parseInt(part.min_stock) ? `<span class="badge bg-danger">Low Stock</span>` : `<span class="badge bg-success">In Stock</span>`;
            tableBody.append(`
                <tr>
                    <td>${escapeHtml(part.part_no)}</td>
                    <td>${escapeHtml(part.description)}</td>
                    <td>${part.current_stock}</td>
                    <td>₱${formatCurrency(part.cost)}</td>
                    <td>₱${formatCurrency(part.price)}</td>
                    <td>₱${formatCurrency(totalValue)}</td>
                    <td>${status}</td>
                    <td>
                        <button class="btn btn-sm btn-primary edit-btn" data-id="${part.id}"><i class="bi bi-pencil-square"></i></button>
                        <button class="btn btn-sm btn-danger delete-btn" data-id="${part.id}"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>`);
        });
        $('.edit-btn').click(e => populateEditModal(inventoryData.find(p => p.id == $(e.currentTarget).data('id'))));
        $('.delete-btn').click(e => { if (confirm('Are you sure?')) deletePart($(e.currentTarget).data('id')); });
    }

    function renderSales(data) {
        const tableBody = $('#salesTableBody');
        tableBody.empty();
        if (!data || data.length === 0) { tableBody.html('<tr><td colspan="7" class="text-center text-muted">No sales found.</td></tr>'); return; }
        data.forEach(item => tableBody.append(`
            <tr>
                <td>${item.transaction_date}</td><td>${escapeHtml(item.part_no)}</td><td>${item.quantity}</td>
                <td>₱${formatCurrency(item.total_amount)}</td><td>${escapeHtml(item.customer_name)}</td>
                <td>${escapeHtml(item.or_number)}</td><td><span class="badge bg-info text-capitalize">${escapeHtml(item.transaction_type)}</span></td>
            </tr>`));
    }

    function renderPayments(data) {
        const tableBody = $('#paymentsTableBody');
        tableBody.empty();
        if (!data || data.length === 0) { tableBody.html('<tr><td colspan="4" class="text-center text-muted">No payments found.</td></tr>'); return; }
        data.forEach(item => tableBody.append(`
            <tr><td>${item.transaction_date}</td><td>${escapeHtml(item.customer_name)}</td>
                <td>₱${formatCurrency(item.amount)}</td><td>${escapeHtml(item.or_number)}</td>
            </tr>`));
    }

    function renderTransfers(data) {
        const tableBody = $('#transfersTableBody');
        tableBody.empty();
        if (!data || data.length === 0) { tableBody.html('<tr><td colspan="6" class="text-center text-muted">No transfers found.</td></tr>'); return; }
        data.forEach(item => {
            const type = item.type === 'TRANSFER_OUT' ? '<span class="badge bg-danger">OUT</span>' : '<span class="badge bg-success">IN</span>';
            tableBody.append(`
            <tr><td>${item.transaction_date}</td><td>${escapeHtml(item.part_no)}</td><td>${item.quantity}</td>
                <td>${type}</td><td>${escapeHtml(item.from_location)}</td><td>${escapeHtml(item.to_location)}</td>
            </tr>`)});
    }
    
    // --- Specific Actions ---
    function deletePart(partId) { /* ... same as before ... */ }
    function populateEditModal(part) { /* ... same as before ... */ }

    // --- Reports Tab Logic ---
    function initializeReports() {
        $('#reportPeriod').off('change').on('change', function() {
            const period = $(this).val();
            ['daily', 'monthly', 'yearly', 'range'].forEach(p => $(`#date-${p}`).toggleClass('d-none', p !== period));
        }).trigger('change');

        const today = new Date().toISOString().split('T')[0];
        $('#reportDateDaily').val(today);
        $('#reportDateMonthly').val(today.substring(0, 7));
        $('#reportDateYearly').val(new Date().getFullYear());
        $('#reportDateStart').val(today);
        $('#reportDateEnd').val(today);
    }
    
    function generateReport() {
        const reportData = {
            type: $('#reportType').val(),
            period: $('#reportPeriod').val(),
            branch: canViewAllBranches ? $('#reportBranch').val() : currentBranch,
        };
        switch (reportData.period) {
            case 'daily': reportData.date_start = $('#reportDateDaily').val(); break;
            case 'monthly': reportData.date_start = $('#reportDateMonthly').val(); break;
            case 'yearly': reportData.date_start = $('#reportDateYearly').val(); break;
            case 'range': 
                reportData.date_start = $('#reportDateStart').val();
                reportData.date_end = $('#reportDateEnd').val();
                break;
        }

        $('#reportResultContainer').html(`<div class="text-center p-5"><div class="spinner-border"></div><p>Generating Report...</p></div>`);
        
        loadApiData('generate_report', reportData, data => {
            $('#reportResultContainer').html(data);
            $('#printReportPdf').off('click').on('click', () => generatePdf('reportResultTable', 'System Report'));
        });
    }


    // --- New Multi-Item Transfer Logic ---
    function initializeTransferModal() {
        transferItems = [];
        $('#transferPartsForm')[0].reset();
        $('#transfer_date').val(new Date().toISOString().split('T')[0]);
        $('#addPartSection').addClass('d-none');
        $('#partSearchResults').empty();
        renderTransferList();
        
        const dropdown = $('#transfer_to_branch');
        if(dropdown.children('option').length <= 1) { // Load if not already loaded
            loadApiData('get_all_branches', {}, data => {
                dropdown.empty().append('<option value="">Select Destination Branch</option>');
                data.forEach(branch => {
                    if (branch !== currentBranch) dropdown.append(`<option value="${escapeHtml(branch)}">${escapeHtml(branch)}</option>`);
                });
            });
        }
    }

    function handlePartSearch() {
        const term = $('#partSearchInput').val();
        if (term.length < 2) { $('#partSearchResults').empty(); return; }
        
        loadApiData('search_inventory_parts', { term: term, branch: currentBranch }, data => {
            const resultsDiv = $('#partSearchResults').empty();
            if (data.length === 0) { resultsDiv.append('<div class="list-group-item">No parts found.</div>'); return; }
            data.forEach(part => {
                const item = $(`<div class="list-group-item list-group-item-action">${part.part_no} - ${part.description} (${part.current_stock} avail)</div>`);
                item.on('click', () => selectPartForTransfer(part));
                resultsDiv.append(item);
            });
        });
    }
    
    function selectPartForTransfer(part) {
        $('#partSearchResults').empty();
        $('#partSearchInput').val('');
        $('#addPartSection').removeClass('d-none').data('part', part);
        $('#selectedPartNo').text(part.part_no);
        $('#selectedPartDesc').text(part.description);
        $('#selectedPartStock').text(part.current_stock);
        $('#quantityToTransfer').attr('max', part.current_stock).focus();
    }

    function addPartToTransferList() {
        const part = $('#addPartSection').data('part');
        const quantity = parseInt($('#quantityToTransfer').val());

        if (!part || !quantity || quantity <= 0) { showErrorModal("Please enter a valid quantity."); return; }
        if (quantity > part.current_stock) { showErrorModal("Quantity exceeds available stock."); return; }
        if (transferItems.find(item => item.part_no === part.part_no)) { showErrorModal("Part is already in the transfer list."); return; }

        transferItems.push({ ...part, quantity: quantity });
        renderTransferList();

        $('#addPartSection').addClass('d-none').data('part', null);
        $('#quantityToTransfer').val('');
    }
    
    function removePartFromTransferList(partNo) {
        transferItems = transferItems.filter(item => item.part_no !== partNo);
        renderTransferList();
    }

    function renderTransferList() {
        const listBody = $('#transferItemsList');
        listBody.empty();
        if (transferItems.length === 0) {
            listBody.append('<tr id="emptyTransferList"><td colspan="4" class="text-center text-muted">No parts added yet.</td></tr>');
        } else {
            transferItems.forEach(item => {
                listBody.append(`
                    <tr>
                        <td>${escapeHtml(item.part_no)}</td><td>${escapeHtml(item.description)}</td>
                        <td>${item.quantity}</td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger remove-transfer-item" data-part="${escapeHtml(item.part_no)}"><i class="bi bi-x-circle"></i></button></td>
                    </tr>`);
            });
        }
    }

    function submitTransfer(event) {
        event.preventDefault();
        const transferData = {
            action: 'transfer_multiple_parts',
            transfer_date: $('#transfer_date').val(),
            from_branch: currentBranch,
            to_branch: $('#transfer_to_branch').val(),
            items: JSON.stringify(transferItems)
        };
        
        if (!transferData.to_branch || transferItems.length === 0) {
            showErrorModal("Please select a destination branch and add at least one part to transfer."); return;
        }

        $.ajax({
            url: '../api/spareparts_inventory.php',
            method: 'POST',
            data: transferData,
            dataType: 'json',
            success: response => {
                if (response.success) {
                    showSuccessModal(response.message);
                    $('#transferPartsModal').modal('hide');
                    loadTransfers();
                } else { showErrorModal(response.message); }
            },
            error: xhr => showErrorModal('API error: ' + xhr.statusText)
        });
    }

    // --- Utility Functions ---
    const generatePdf = (tableId, title) => { const { jsPDF } = window.jspdf; const doc = new jsPDF(); doc.text(title, 14, 15); doc.autoTable({ html: `#${tableId}`, startY: 20, theme: 'grid' }); doc.save(`${title.replace(/ /g, '_')}.pdf`); };
    const showSuccessModal = message => { $('#successMessage').text(message); $('#successModal').modal('show'); };
    const showErrorModal = message => { $('#errorMessage').text(message); $('#errorModal').modal('show'); };
    const formatCurrency = amount => Number(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const filterData = (data, query, fields) => { if (!query) return data; const q = query.toLowerCase(); return data.filter(item => fields.some(field => item[field] && item[field].toString().toLowerCase().includes(q))); };
    const escapeHtml = text => { const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }; return String(text || '').replace(/[&<>"']/g, m => map[m]); };
});