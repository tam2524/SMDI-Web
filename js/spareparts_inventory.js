$(document).ready(function() {
    
    let inventoryData = [], salesData = [], paymentsData = [], transfersData = [], incomingTransfersData = [];
    
    
    loadDashboardStats();
    loadInventory(); 
    setupEventListeners();

    
    function setupEventListeners() {
        $('button[data-bs-toggle="tab"]').on('shown.bs.tab', e => {
            const targetTab = $(e.target).attr('id');
            switch (targetTab) {
                case 'dashboard-tab': loadDashboardStats(); break;
                case 'inventory-tab': loadInventory(); break;
                case 'sales-tab': loadSales(); break;
                case 'payments-tab': loadPayments(); break;
                case 'transfer-tab': loadTransfers(); break;
                case 'incoming-transfer-tab': loadIncomingTransfers(); break; 
            }
        });

        
        $('#addPartsInForm').submit(e => submitGenericForm(e, 'add_multiple_parts_in', '#addPartsInModal', [loadInventory, loadDashboardStats]));
        $('#editPartForm').submit(e => submitGenericForm(e, 'edit_parts', '#editPartModal', [loadInventory, loadDashboardStats]));
        
        
        $('#sellPartsOutForm').submit(submitSellPartsOut);
        $('#recordPaymentForm').submit(submitRecordPayment);
        $('#transferPartsForm').submit(submitTransfer);

        
        $('#inventorySearch').on('keyup', e => renderInventory(filterData(inventoryData, $(e.target).val(), ['part_no', 'description'])));
        $('#salesSearch').on('keyup', e => renderSales(filterData(salesData, $(e.target).val(), ['customer_name', 'or_number'])));
        $('#paymentsSearch').on('keyup', e => renderPayments(filterData(paymentsData, $(e.target).val(), ['customer_name', 'or_number'])));
        $('#transfersSearch').on('keyup', e => renderTransfers(filterData(transfersData, $(e.target).val(), ['part_no', 'to_location'])));

        
        $('#printInventoryBtn').on('click', () => generatePdf('inventoryTable', 'Inventory Report'));
        $('#printSalesBtn').on('click', () => generatePdf('salesTable', 'Sales Report'));
        $('#printPaymentsBtn').on('click', () => generatePdf('paymentsTable', 'Payments Report'));
        $('#printTransfersBtn').on('click', () => generatePdf('transfersTable', 'Outgoing Transfers Report'));

        
        $('#sellPartsOutModal').on('show.bs.modal', initializeSellPartsModal);
        $('#salePartSearchInput').on('keyup', handleSalePartSearch);
        $('#partsForSaleList').on('click', '.remove-sale-item', function() { $(this).closest('tr').remove(); updateSaleTotal(); });
        $('#partsForSaleList').on('input', '.sale-qty, .sale-price', updateSaleTotal);
        
        
        $('#recordPaymentModal').on('show.bs.modal', () => { $('#recordPaymentForm')[0].reset(); $('#customerSearchResults').empty(); $('#balance_info').text('Enter the OR number from the installment sale.');});
        $('#payment_customer_search').on('keyup', handleCustomerSearch);

        
        $('#transferPartsModal').on('show.bs.modal', initializeTransferModal);
        
    }

    
    function loadApiData(endpoint, successCallback, tableBodyId = null, data = {}) {
        if (tableBodyId) $(`#${tableBodyId}`).html(`<tr><td colspan="10" class="text-center py-5"><div class='spinner-border text-primary'></div></td></tr>`);
        
        $.ajax({
            url: `../api/spareparts_inventory.php?action=${endpoint}`,
            method: 'GET',
            data: data,
            dataType: 'json',
            success: response => {
                if (response.success) {
                    successCallback(response.data);
                } else {
                    showErrorModal(response.message || 'An unknown error occurred.');
                    if (tableBodyId) $(`#${tableBodyId}`).html(`<tr><td colspan="10" class="text-center text-muted">Failed to load data.</td></tr>`);
                }
            },
            error: xhr => {
                showErrorModal('API connection failed: ' + xhr.statusText);
                if (tableBodyId) $(`#${tableBodyId}`).html(`<tr><td colspan="10" class="text-center text-danger">API connection failed.</td></tr>`);
            }
        });
    }

    
    function loadDashboardStats() {
        $('#dashboard-content').addClass('d-none');
        $('#dashboard-loader').removeClass('d-none');
        loadApiData('get_dashboard_stats', data => {
            $('#stat-total-qty').text(data.total_quantity || 0);
            $('#stat-inventory-value').text(`₱${formatCurrency(data.total_value)}`);
            $('#stat-monthly-sales').text(`₱${formatCurrency(data.monthly_sales)}`);
            $('#stat-yearly-sales').text(`₱${formatCurrency(data.yearly_sales)}`);
            $('#stat-outstanding-balance').text(`₱${formatCurrency(data.outstanding_balance)}`);
            $('#stat-total-accounts').text(data.total_accounts || 0);
            $('#dashboard-loader').addClass('d-none');
            $('#dashboard-content').removeClass('d-none');
        });
    }

    function loadInventory() { loadApiData('get_inventory_list', data => { inventoryData = data; renderInventory(data); }, 'inventoryTableBody'); }
    function loadSales() { loadApiData('get_sales_list', data => { salesData = data; renderSales(data); }, 'salesTableBody'); }
    function loadPayments() { loadApiData('get_payments_list', data => { paymentsData = data; renderPayments(data); }, 'paymentsTableBody'); }
    function loadTransfers() { loadApiData('get_transfers_list', data => { transfersData = data; renderTransfers(data); }, 'transfersTableBody'); }
    
    function loadIncomingTransfers() {
        loadApiData('get_incoming_transfers', data => {
            incomingTransfersData = data;
            if (data.length > 0) {
                $('#incoming-badge').text(data.length).removeClass('d-none');
            } else {
                $('#incoming-badge').addClass('d-none');
            }
            renderIncomingTransfers(data);
        }, 'incomingTransfersTableBody');
    }

    
    function renderInventory(data) {
        const tableBody = $('#inventoryTableBody');
        tableBody.empty();
        if (!data || data.length === 0) { tableBody.html('<tr><td colspan="8" class="text-center text-muted p-4">No inventory found.</td></tr>'); return; }
        
        data.forEach(part => {
            const totalValue = part.current_stock * part.cost;
            const status = parseInt(part.current_stock) <= 0 ? `<span class="badge bg-danger">Out of Stock</span>` : (parseInt(part.current_stock) <= parseInt(part.min_stock) ? `<span class="badge bg-warning text-dark">Low Stock</span>` : `<span class="badge bg-success">In Stock</span>`);
            const deleteBtn = canDelete ? `<button class="btn btn-sm btn-danger delete-btn" data-type="part" data-id="${part.id}"><i class="bi bi-trash"></i></button>` : '';
            const rowClass = parseInt(part.current_stock) === 0 ? 'stock-zero' : '';
            tableBody.append(`
                <tr class="${rowClass}">
                    <td>${escapeHtml(part.part_no)}</td> <td>${escapeHtml(part.description)}</td>
                    <td>${part.current_stock}</td> <td>₱${formatCurrency(part.cost)}</td>
                    <td>₱${formatCurrency(part.price)}</td> <td>₱${formatCurrency(totalValue)}</td>
                    <td>${status}</td>
                    <td>
                        <button class="btn btn-sm btn-primary edit-btn" data-id="${part.id}"><i class="bi bi-pencil-square"></i></button>
                        ${deleteBtn}
                    </td>
                </tr>`);
        });
        $('.edit-btn').click(e => populateEditModal(inventoryData.find(p => p.id == $(e.currentTarget).data('id'))));
        $('.delete-btn').click(handleDelete);
    }

    function renderSales(data) {
        const tableBody = $('#salesTableBody');
        tableBody.empty();
        if (!data || data.length === 0) { tableBody.html('<tr><td colspan="7" class="text-center text-muted p-4">No sales found.</td></tr>'); return; }
        data.forEach(sale => {
            const balanceText = sale.transaction_type === 'installment' ? `<strong class="${parseFloat(sale.balance) > 0 ? 'text-danger' : 'text-success'}">₱${formatCurrency(sale.balance)}</strong>` : 'N/A';
            const deleteBtn = canDelete ? `<button class="btn btn-sm btn-danger delete-btn" data-type="sale" data-id="${sale.or_number}"><i class="bi bi-trash"></i></button>` : '';
            tableBody.append(`
                <tr>
                    <td>${sale.sale_date}</td> <td>${escapeHtml(sale.customer_name)}</td>
                    <td>${escapeHtml(sale.or_number)}</td> <td>₱${formatCurrency(sale.total_amount)}</td>
                    <td><span class="badge bg-info text-capitalize">${escapeHtml(sale.transaction_type)}</span></td>
                    <td>${balanceText}</td>
                    <td>${deleteBtn}</td>
                </tr>`);
        });
        $('.delete-btn').click(handleDelete);
    }

    function renderPayments(data) {
        const tableBody = $('#paymentsTableBody');
        tableBody.empty();
        if (!data || data.length === 0) { tableBody.html('<tr><td colspan="5" class="text-center text-muted p-4">No payments found.</td></tr>'); return; }
        data.forEach(payment => {
            const deleteBtn = canDelete ? `<button class="btn btn-sm btn-danger delete-btn" data-type="payment" data-id="${payment.id}"><i class="bi bi-trash"></i></button>` : '';
            tableBody.append(`
                <tr>
                    <td>${payment.transaction_date}</td> <td>${escapeHtml(payment.customer_name)}</td>
                    <td>₱${formatCurrency(payment.amount)}</td> <td>${escapeHtml(payment.or_number)}</td>
                    <td>${deleteBtn}</td>
                </tr>`);
        });
        $('.delete-btn').click(handleDelete);
    }

    function renderTransfers(data) {
        const tableBody = $('#transfersTableBody');
        tableBody.empty();
        if (!data || data.length === 0) { tableBody.html('<tr><td colspan="7" class="text-center text-muted p-4">No outgoing transfers found.</td></tr>'); return; }
        data.forEach(transfer => {
            const statusBadge = transfer.status === 'In-Transit' ? `<span class="badge bg-warning text-dark">In-Transit</span>` : (transfer.status === 'Completed' ? `<span class="badge bg-success">Completed</span>` : `<span class="badge bg-secondary">${transfer.status}</span>`);
            const cancelBtn = canDelete && transfer.status === 'In-Transit' ? `<button class="btn btn-sm btn-outline-danger delete-btn" data-type="transfer" data-id="${transfer.id}"><i class="bi bi-x-circle"></i> Cancel</button>` : '';
            tableBody.append(`
                <tr>
                    <td>${transfer.transfer_date}</td>
                    <td>${transfer.item_count} item(s)</td>
                    <td>${escapeHtml(transfer.from_branch)}</td>
                    <td>${escapeHtml(transfer.to_branch)}</td>
                    <td>${statusBadge}</td>
                    <td>${cancelBtn}</td>
                </tr>`);
        });
        $('.delete-btn').click(handleDelete);
    }
    
    function renderIncomingTransfers(data) {
        const tableBody = $('#incomingTransfersTableBody');
        tableBody.empty();
        if (!data || data.length === 0) { tableBody.html('<tr><td colspan="4" class="text-center text-muted p-4">No pending incoming transfers.</td></tr>'); return; }
        data.forEach(transfer => {
            tableBody.append(`
                <tr>
                    <td>${transfer.transfer_date}</td>
                    <td>${escapeHtml(transfer.from_branch)}</td>
                    <td>${transfer.item_count} item(s)</td>
                    <td><button class="btn btn-sm btn-success view-incoming-btn" data-id="${transfer.id}"><i class="bi bi-box-arrow-in-down me-1"></i> View & Receive</button></td>
                </tr>`);
        });
        $('.view-incoming-btn').click(function() {
            const transferId = $(this).data('id');
            const transfer = incomingTransfersData.find(t => t.id == transferId);
            populateIncomingTransferModal(transfer);
        });
    }

    
    function handleDelete(event) {
        const button = $(event.currentTarget);
        const type = button.data('type');
        const id = button.data('id');
        let message = `Are you sure you want to delete this ${type}? This action cannot be undone.`;
        if (type === 'sale') message = `This will return all items from sale OR# ${id} back to inventory. Continue?`;
        if (type === 'payment') message = `This will reverse the payment and add the amount back to the customer's balance. Continue?`;
        if (type === 'transfer') message = `This will cancel the 'In-Transit' transfer and return all items to your stock. Continue?`;
        
        if (confirm(message)) {
            $.ajax({
                url: '../api/spareparts_inventory.php',
                method: 'POST',
                data: { action: `delete_${type}`, id: id },
                dataType: 'json',
                success: response => {
                    if (response.success) {
                        showSuccessModal(response.message);
                        
                        if (type === 'part') { loadInventory(); loadDashboardStats(); }
                        if (type === 'sale') { loadSales(); loadInventory(); loadDashboardStats(); }
                        if (type === 'payment') { loadPayments(); loadSales(); loadDashboardStats(); }
                        if (type === 'transfer') { loadTransfers(); loadInventory(); loadDashboardStats(); }
                    } else { showErrorModal(response.message); }
                },
                error: xhr => showErrorModal('API Error: ' + xhr.statusText)
            });
        }
    }
    
    
    function initializeSellPartsModal() {
        $('#sellPartsOutForm')[0].reset();
        $('#out_date').val(new Date().toISOString().split('T')[0]);
        $('#partsForSaleList').html('<tr id="emptySaleListRow"><td colspan="5" class="text-center text-muted p-4">Cart is empty</td></tr>');
        $('#salePartSearchResults').empty();
        updateSaleTotal();
    }
    
    function handleSalePartSearch() {
        const term = $('#salePartSearchInput').val();
        if (term.length < 2) { $('#salePartSearchResults').empty(); return; }
        
        loadApiData('search_inventory_parts', data => {
            const resultsDiv = $('#salePartSearchResults').empty();
            if (data.length === 0) { resultsDiv.append('<div class="list-group-item text-muted">No parts found.</div>'); return; }
            data.forEach(part => {
                const item = $(`<a href="#" class="list-group-item list-group-item-action">
                                <div><strong>${escapeHtml(part.part_no)}</strong> - <small>${escapeHtml(part.description)}</small></div>
                                <small>Stock: <span class="badge bg-success">${part.current_stock}</span> | Price: <span class="badge bg-primary">₱${formatCurrency(part.price)}</span></small>
                              </a>`);
                item.on('click', (e) => { e.preventDefault(); addPartToSaleList(part); });
                resultsDiv.append(item);
            });
        }, null, { term: term });
    }

    function addPartToSaleList(part) {
        if ($(`#partsForSaleList tr[data-part-no="${part.part_no}"]`).length > 0) { showErrorModal("This part is already in the sale list."); return; }
        $('#emptySaleListRow').remove();
        const row = `<tr data-part-no="${escapeHtml(part.part_no)}" style="vertical-align: middle;">
                        <td><div class="fw-bold">${escapeHtml(part.part_no)}</div><small class="text-muted">${escapeHtml(part.description)}</small></td>
                        <td class="text-center"><input type="number" class="form-control form-control-sm sale-qty" style="width: 70px; margin: auto;" min="1" max="${part.current_stock}" value="1" required></td>
                        <td class="text-end"><div class="input-group input-group-sm"><span class="input-group-text">₱</span><input type="number" step="0.01" class="form-control form-control-sm sale-price text-end" min="0" value="${parseFloat(part.price || 0).toFixed(2)}" required></div></td>
                        <td class="text-end fw-bold line-subtotal"></td>
                        <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-sale-item"><i class="bi bi-x-lg"></i></button></td>
                     </tr>`;
        $('#partsForSaleList').append(row);
        $('#salePartSearchInput').val('').focus();
        $('#salePartSearchResults').empty();
        updateSaleTotal();
    }

    function updateSaleTotal() {
        let grandTotal = 0;
        $('#partsForSaleList tr').each(function() {
            if ($(this).attr('id') === 'emptySaleListRow') return;
            const row = $(this);
            const qty = parseInt(row.find('.sale-qty').val()) || 0;
            const price = parseFloat(row.find('.sale-price').val()) || 0;
            const lineTotal = qty * price;
            row.find('.line-subtotal').text(`₱${formatCurrency(lineTotal)}`);
            grandTotal += lineTotal;
        });
        $('#pos-grand-total').text(`₱${formatCurrency(grandTotal)}`);
        if ($('#partsForSaleList tr[data-part-no]').length === 0) {
             $('#partsForSaleList').html('<tr id="emptySaleListRow"><td colspan="5" class="text-center text-muted p-4">Cart is empty</td></tr>');
        } else {
            $('#emptySaleListRow').remove();
        }
    }

    function submitSellPartsOut(event) {
        event.preventDefault();
        const items = [];
        let isValid = true;
        
        $('#partsForSaleList tr[data-part-no]').each(function() {
            const row = $(this);
            const part_no = row.data('part-no');
            const quantity = parseInt(row.find('.sale-qty').val());
            const maxQty = parseInt(row.find('.sale-qty').attr('max'));
            const unitPrice = parseFloat(row.find('.sale-price').val());

            if (quantity > maxQty) { showErrorModal(`Quantity for ${part_no} exceeds available stock of ${maxQty}.`); isValid = false; return false; }
            if (!quantity || quantity <= 0) { showErrorModal(`Invalid quantity for ${part_no}.`); isValid = false; return false; }
            
            items.push({ part_no: part_no, quantity: quantity, price: unitPrice });
        });

        if (!isValid) return;
        if (items.length === 0) { showErrorModal("Cannot record a sale with no items."); return; }

        const formData = {
            action: 'sell_multiple_parts_out',
            date: $('#out_date').val(),
            or_number: $('#out_or_number').val(),
            customer_name: $('#out_customer_name').val(),
            transaction_type: $('#out_transaction_type').val(),
            items: JSON.stringify(items)
        };

        $.ajax({
            url: '../api/spareparts_inventory.php', method: 'POST', data: formData, dataType: 'json',
            success: response => {
                if (response.success) {
                    $('#sellPartsOutModal').modal('hide');
                    showSaleReceipt(formData, items);
                    loadSales(); loadInventory(); loadDashboardStats();
                } else { showErrorModal(response.message); }
            },
            error: xhr => showErrorModal('API Error: ' + xhr.statusText)
        });
    }

    
    function handleCustomerSearch() {
        const term = $('#payment_customer_search').val();
        if (term.length < 2) { $('#customerSearchResults').empty(); return; }
        
        loadApiData('search_customer_accounts', results => {
            const resultsDiv = $('#customerSearchResults').empty();
            if(results.length === 0) { resultsDiv.append(`<div class="list-group-item text-muted">No accounts found.</div>`); return; }
            results.forEach(acc => {
                const item = $(`<a href="#" class="list-group-item list-group-item-action"><strong>${escapeHtml(acc.or_number)}</strong><br><small>Balance: ₱${formatCurrency(acc.balance)} | Date: ${acc.sale_date}</small></a>`);
                item.on('click', e => {
                    e.preventDefault();
                    $('#payment_or_number').val(acc.or_number);
                    $('#balance_info').html(`Current Balance: <strong class="text-danger">₱${formatCurrency(acc.balance)}</strong>`);
                    $('#payment_amount').val(parseFloat(acc.balance).toFixed(2)).focus();
                    $('#customerSearchResults').empty();
                });
                resultsDiv.append(item);
            });
        }, null, { term: term });
    }

    function submitRecordPayment(event) {
        event.preventDefault();
        const formData = {
            action: 'record_payment',
            payment_date: $('#payment_date').val(),
            or_number: $('#payment_or_number').val(),
            amount: $('#payment_amount').val(),
            customer_name: 'N/A' 
        };
        $.ajax({
            url: '../api/spareparts_inventory.php', method: 'POST', data: formData, dataType: 'json',
            success: response => {
                if (response.success) {
                    formData.customer_name = response.customer_name;
                    $('#recordPaymentModal').modal('hide');
                    showPaymentReceipt(formData);
                    loadPayments(); loadSales(); loadDashboardStats();
                } else { showErrorModal(response.message); }
            },
            error: xhr => showErrorModal('API Error: ' + xhr.statusText)
        });
    }
    
    
    function populateIncomingTransferModal(transfer) {
        const body = $('#incomingTransferDetailsBody');
        body.html(`<p><strong>From:</strong> ${escapeHtml(transfer.from_branch)}</p><p><strong>Date Sent:</strong> ${transfer.transfer_date}</p><hr><h5>Items to Receive:</h5>`);
        const table = $('<table class="table table-sm"><thead><tr><th>Part No</th><th>Description</th><th>Qty</th></tr></thead><tbody></tbody></table>');
        transfer.items.forEach(item => {
            table.find('tbody').append(`<tr><td>${escapeHtml(item.part_no)}</td><td>${escapeHtml(item.description)}</td><td>${item.quantity}</td></tr>`);
        });
        body.append(table);
        $('#confirmReceiveBtn').data('id', transfer.id);
        $('#viewIncomingTransferModal').modal('show');
    }

    $('#confirmReceiveBtn').click(function() {
        const transferId = $(this).data('id');
        if (!confirm("Are you sure you want to receive these items? This will add them to your inventory.")) return;
        
        $.ajax({
            url: '../api/spareparts_inventory.php', method: 'POST', data: { action: 'accept_transfer', transfer_id: transferId }, dataType: 'json',
            success: response => {
                if(response.success) {
                    showSuccessModal(response.message);
                    $('#viewIncomingTransferModal').modal('hide');
                    loadIncomingTransfers(); loadInventory(); loadDashboardStats();
                } else { showErrorModal(response.message); }
            },
            error: xhr => showErrorModal('API Error: ' + xhr.statusText)
        });
    });

    
    function showSaleReceipt(formData, items) {
        let total = 0;
        let itemsHtml = items.map(item => {
            const subtotal = item.quantity * item.price;
            total += subtotal;
            return `<tr><td>${escapeHtml(item.part_no)}</td><td class="text-center">${item.quantity}</td><td class="text-end">₱${formatCurrency(item.price)}</td><td class="text-end">₱${formatCurrency(subtotal)}</td></tr>`;
        }).join('');
        
        const receiptHtml = `
            <h4>Official Receipt</h4><hr>
            <p><strong>Date:</strong> ${formData.date}</p>
            <p><strong>Customer:</strong> ${escapeHtml(formData.customer_name)}</p>
            <p><strong>OR Number:</strong> ${escapeHtml(formData.or_number)}</p>
            <table class="table"><thead><tr><th>Part No</th><th class="text-center">Qty</th><th class="text-end">Price</th><th class="text-end">Subtotal</th></tr></thead><tbody>${itemsHtml}</tbody></table>
            <h5 class="text-end">Total: ₱${formatCurrency(total)}</h5>`;
        
        $('#receiptTitle').text('Sale Receipt');
        $('#receiptBody').html(receiptHtml);
        $('#receiptModal').modal('show');
    }
    
    function showPaymentReceipt(formData) {
        const receiptHtml = `
            <h4>Payment Acknowledgment</h4><hr>
            <p><strong>Payment Date:</strong> ${formData.payment_date}</p>
            <p><strong>Customer Name:</strong> ${escapeHtml(formData.customer_name)}</p>
            <p><strong>Original OR:</strong> ${escapeHtml(formData.or_number)}</p>
            <h4 class="mt-3">Amount Paid: ₱${formatCurrency(formData.amount)}</h4>`;
            
        $('#receiptTitle').text('Payment Receipt');
        $('#receiptBody').html(receiptHtml);
        $('#receiptModal').modal('show');
    }
    
    function showTransferSlip(formData, items) {
        let itemsHtml = items.map(item => `<tr><td>${escapeHtml(item.part_no)}</td><td>${escapeHtml(item.description)}</td><td class="text-center">${item.quantity}</td></tr>`).join('');
        const slipHtml = `
            <h4>Inventory Transfer Slip</h4><hr>
            <p><strong>Date:</strong> ${formData.transfer_date}</p>
            <p><strong>From Branch:</strong> ${currentBranch}</p>
            <p><strong>To Branch:</strong> ${escapeHtml(formData.to_branch)}</p>
            <p><strong>Status:</strong> <span class="badge bg-warning text-dark">IN-TRANSIT</span></p>
            <table class="table"><thead><tr><th>Part No</th><th>Description</th><th class="text-center">Qty</th></tr></thead><tbody>${itemsHtml}</tbody></table>`;
            
        $('#receiptTitle').text('Transfer Slip');
        $('#receiptBody').html(slipHtml);
        $('#receiptModal').modal('show');
    }

    
    const generatePdf = (tableId, title) => { const { jsPDF } = window.jspdf; const doc = new jsPDF(); doc.text(title, 14, 15); doc.autoTable({ html: `#${tableId}`, startY: 20 }); doc.save(`${title.replace(/ /g, '_')}.pdf`); };
    const showSuccessModal = message => { $('#successMessage').text(message); $('#successModal').modal('show'); };
    const showErrorModal = message => { $('#errorMessage').text(message); $('#errorModal').modal('show'); };
    const formatCurrency = amount => Number(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const filterData = (data, query, fields) => { if (!query) return data; const q = query.toLowerCase(); return data.filter(item => fields.some(field => item[field] && String(item[field]).toLowerCase().includes(q))); };
    const escapeHtml = text => { const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }; return String(text || '').replace(/[&<>"']/g, m => map[m]); };
});