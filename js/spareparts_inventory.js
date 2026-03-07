$(document).ready(function () {
    let inventoryData = [], salesData = [], paymentsData = [], transfersData = [], incomingTransfersData = [], activityLogData = [], globalTransfersData = [], paymentsAgingData = [];
    let saleCart = [], transferCart = [];
    const isBranchPage = typeof window.isBranchPage !== 'undefined' ? window.isBranchPage : window.location.pathname.includes('branch_spareparts');
    const isAdminPage = window.location.pathname.includes('admin_spareparts') || window.location.pathname.includes('headoffice_spareparts');
    const canDelete = typeof window.canDelete !== 'undefined' ? window.canDelete : false;

    // Utility Functions
    function formatCurrency(amount) { return Number(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function formatDateTime(dateStr) { if (!dateStr) return 'N/A'; const d = new Date(dateStr); return d.toLocaleString('en-US', { year: 'numeric', month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit' }); }
    function escapeHtml(text) { const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }; return String(text || '').replace(/[&<>"']/g, m => map[m]); }
    function filterData(data, query, fields) { if (!query) return data; const q = query.toLowerCase(); return data.filter(item => fields.some(field => item[field] && String(item[field]).toLowerCase().includes(q))); }
    function showSuccessModal(message) { $('#successMessage').text(message); $('#successModal').modal('show'); }
    function showErrorModal(message) { $('#errorMessage').text(message); $('#errorModal').modal('show'); }

    function showConfirmModal(message, onConfirm) {
        try {
            $('#confirmModalMessage').text(message);
            const modalEl = document.getElementById('customConfirmModal');

            // Recreate the button to cleanly remove all old event listeners
            const oldBtn = document.getElementById('confirmModalNext');
            const newBtn = oldBtn.cloneNode(true);
            oldBtn.parentNode.replaceChild(newBtn, oldBtn);

            // Get or create Bootstrap modal instance
            let modalObj = bootstrap.Modal.getInstance(modalEl);
            if (!modalObj) {
                modalObj = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false });
            }

            // When user confirms, hide modal and execute callback
            newBtn.addEventListener('click', function () {
                modalObj.hide();
                if (typeof onConfirm === 'function') onConfirm();
            });

            // Force z-index of the modal directly just in case
            modalEl.style.zIndex = "2150";
            modalObj.show();

            // Give it 100ms to inject the backdrop, then force the backdrop's z-index to 2140
            setTimeout(() => {
                const backdrops = document.querySelectorAll('.modal-backdrop');
                if (backdrops.length > 0) {
                    // Pick the last backdrop added (which should belong to the top modal)
                    backdrops[backdrops.length - 1].style.zIndex = "2140";
                }
            }, 100);
        } catch (e) {
            console.error("showConfirmModal failed:", e);
            // Fallback to native browser confirm if Bootstrap logic completely fails
            if (confirm(message)) {
                if (typeof onConfirm === 'function') onConfirm();
            }
        }
    }

    // Core Loading Logic
    function loadApiData(endpoint, successCallback, tableBodyId = null, data = {}) {
        if (tableBodyId) $(`#${tableBodyId}`).html(`<tr><td colspan="12" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>`);

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
                    if (tableBodyId) $(`#${tableBodyId}`).html(`<tr><td colspan="12" class="text-center text-muted">Failed to load data.</td></tr>`);
                }
            },
            error: xhr => {
                showErrorModal('API connection failed: ' + xhr.statusText);
                if (tableBodyId) $(`#${tableBodyId}`).html(`<tr><td colspan="12" class="text-center text-danger">API connection failed.</td></tr>`);
            }
        });
    }

    function setupEventListeners() {
        // Main Navigation Tabs
        $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
            const targetTab = this.id || $(e.target).attr('id');
            const targetPane = $(e.target).attr('data-bs-target');

            switch (targetTab) {
                case 'dashboard-tab': loadDashboardStats(); break;
                case 'find-tab': /* Logic for find already handled by search buttons */ break;
                // Branch-specific flattened tabs
                case 'inventory-tab': loadInventory(); break;
                case 'sales-tab': loadSales(); break;
                case 'payments-tab': loadPayments(); break;
                case 'transfer-tab':
                case 'transfer-out-tab': loadTransfers(); break;
                case 'transfer-in-tab':
                case 'incoming-transfer-tab': loadIncomingTransfers(); break;

                // Head Office / Admin specific tabs
                case 'global-transfer-tab': loadGlobalTransfers(); break;
                case 'activity-log-tab': loadActivityLog(); break;
            }

            // Also handle by target pane just in case ID is inconsistent
            if (!targetTab) {
                switch (targetPane) {
                    case '#sub-stock': loadInventory(); break;
                    case '#sub-sales': loadSales(); break;
                    case '#sub-payments': loadPayments(); break;
                    case '#sub-transfer-out': loadTransfers(); break;
                    case '#sub-transfer-in': loadIncomingTransfers(); break;
                }
            }
        });

        // Search Handlers
        $('#inventorySearch').on('input', e => renderInventory(filterData(inventoryData, $(e.target).val(), ['part_no', 'description', 'brand'])));
        $('#salesSearch').on('input', e => renderSales(filterData(salesData, $(e.target).val(), ['customer_name', 'or_number'])));
        $('#paymentsSearch').on('input', e => renderPayments(filterData(paymentsData, $(e.target).val(), ['customer_name', 'or_number'])));
        $('#transfersSearch').on('input', e => renderTransfers(filterData(transfersData, $(e.target).val(), ['from_branch', 'to_branch'])));
        $('#paymentsAgingSearch').on('input', () => loadAging());
        $('#agingBranchFilter').on('change', () => loadAging());

        $('#paymentsAgingModal').on('shown.bs.modal', function () {
            loadAging();
        });

        $('#printAgingSummaryBtn').on('click', function () {
            printAgingSummary();
        });

        $(document).on('click', '.print-ledger-btn', function (e) {
            e.stopPropagation();
            const customer = $(this).data('customer');
            const branch = $(this).data('branch');
            printIndividualAging(customer, branch);
        });

        // Sales Sub-tabs filtering
        $('#salesSubTabs button').on('click', function () {
            const type = $(this).data('type');
            renderSales(salesData, type);
        });

        // Populating Branches
        loadBranches();

        function loadBranches() {
            $.get('../api/spareparts_inventory.php?action=get_branches', response => {
                if (response.success) {
                    // For general selections (Transfers, Filters)
                    let baseHtml = '<option value="" disabled selected>Select branch...</option>';
                    response.data.forEach(branch => {
                        baseHtml += `<option value="${branch}">${branch}</option>`;
                    });
                    $('#to_branch, #agingBranchFilter, #edit_branch').html(baseHtml);

                    // For Reports (allowing "All Branches")
                    let reportHtml = isAdminPage ? '<option value="all">All Branches</option>' : '';
                    response.data.forEach(branch => {
                        reportHtml += `<option value="${branch}">${branch}</option>`;
                    });

                    const reportSelectors = '#inv_report_branch, #sales_report_branch, #payment_report_branch, #t_report_branch';
                    $(reportSelectors).html(reportHtml);

                    // If branch page, lock the selection to the current branch
                    if (isBranchPage && typeof window.currentBranch !== 'undefined') {
                        $(reportSelectors).val(window.currentBranch).prop('disabled', true);
                    }
                }
            }, 'json');
        }

        // INVENTORY TAB BUTTON HANDLERS
        $(document).on('click', '.edit-part-btn', function () {
            const id = $(this).data('id');
            const item = inventoryData.find(i => i.id == id);
            if (!item) return;

            $('#edit_part_id').val(item.id);
            $('#edit_branch').val(item.current_branch);
            $('#edit_brand').val(item.brand);
            $('#edit_part_no').val(item.part_no);
            $('#edit_stock').val(item.current_stock);
            $('#edit_description').val(item.description);
            $('#edit_cost').val(item.cost);
            $('#edit_price').val(item.price);
            $('#edit_min_stock').val(item.min_stock);
            $('#edit_invoice_no').val(item.invoice_no || '');
            $('#edit_change_reason').val('');

            $('#editPartModal').modal('show');
        });

        $(document).on('click', '.view-history-btn', function () {
            const id = $(this).data('id');
            const item = inventoryData.find(i => i.id == id);
            if (!item) return;

            $('#historyPartDescription').text(item.description);
            $('#historyPartBrand').text(item.brand);
            $('#historyPartNumber').text(item.part_no);
            $('#historyTableBody').html('<tr><td colspan="6" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div> Loading...</td></tr>');
            $('#viewHistoryModal').modal('show');

            loadApiData('get_inventory_history', data => {
                renderInventoryHistory(data);
            }, 'historyTableBody', { id: id });
        });

        $(document).on('click', '.delete-part-btn', function () {
            const id = $(this).data('id');
            showConfirmModal('Are you sure you want to delete this inventory item? This action cannot be undone.', function () {
                $.post(`../api/spareparts_inventory.php?action=delete_part`, { id: id }, response => {
                    if (response.success) {
                        showSuccessModal('Part deleted successfully.');
                        loadInventory();
                    } else {
                        showErrorModal(response.message);
                    }
                }, 'json');
            });
        });

        $(document).on('click', '.edit-sale-btn', function () {
            const or = $(this).data('or');
            const branch = $(this).data('branch');
            const sale = salesData.find(s => s.or_number == or && s.from_location == branch);
            if (!sale) return;

            // Populate hidden identifiers for the update query
            $('#edit_sale_original_or').val(sale.or_number);
            $('#edit_sale_original_branch').val(sale.from_location);

            // Populate display-only identity info
            $('#edit_sale_or_display').text(sale.or_number);
            $('#edit_sale_branch_display').text(sale.from_location);

            // Populate editable fields
            $('#edit_sale_or').val(sale.or_number);
            $('#edit_sale_customer').val(sale.customer_name);
            $('#edit_sale_date').val(sale.sale_date);
            $('#edit_sale_amount').val(sale.total_amount);
            $('#edit_sale_type').val(sale.transaction_type);
            $('#edit_sale_reason').val('');

            // Populate branch select if not already populated
            const branchSelect = $('#edit_sale_branch');
            if (branchSelect.find('option').length <= 0) {
                // Assuming you have a list of branches available, or fetch them
                $.get('../api/spareparts_inventory.php?action=get_branches', response => {
                    if (response.success) {
                        let html = '';
                        response.data.forEach(b => {
                            html += `<option value="${b}">${b}</option>`;
                        });
                        branchSelect.html(html).val(sale.from_location);
                    }
                }, 'json');
            } else {
                branchSelect.val(sale.from_location);
            }

            $('#editSaleModal').modal('show');
        });

        $(document).on('click', '.view-sale-btn', function () {
            const or = $(this).data('or');
            const branch = $(this).data('branch');
            $('#saleDetailsContent').html('<div class="text-center p-5"><div class="spinner-border text-primary"></div><p class="mt-2">Loading sale details...</p></div>');
            $('#viewSaleDetailsModal').modal('show');

            $.get('../api/spareparts_inventory.php?action=get_sale_details', { or_number: or, branch: branch }, response => {
                if (response.success) {
                    renderSaleDetails(response.data);
                } else {
                    showErrorModal(response.message);
                    $('#viewSaleDetailsModal').modal('hide');
                }
            }, 'json');
        });

        $(document).on('click', '.delete-sale-btn', function () {
            const id = $(this).data('id');
            const branch = $(this).data('branch');
            showConfirmModal('Are you sure you want to delete this sale record? Inventory will be returned to stock.', function () {
                $.post(`../api/spareparts_inventory.php?action=delete_sale`, { id: id, branch: branch }, response => {
                    if (response.success) {
                        showSuccessModal('Sale record deleted successfully.');
                        loadSales();
                        loadInventory();
                        loadDashboardStats();
                    } else {
                        showErrorModal(response.message);
                    }
                }, 'json');
            });
        });

        $(document).on('click', '.delete-payment-btn', function () {
            const id = $(this).data('id');
            const branch = $(this).data('branch');
            showConfirmModal('Are you sure you want to delete this payment record? Balance will be updated.', function () {
                $.post(`../api/spareparts_inventory.php?action=delete_payment`, { id: id, branch: branch }, response => {
                    if (response.success) {
                        showSuccessModal('Payment record deleted successfully.');
                        loadPayments();
                        if ($('#paymentsAgingModal').hasClass('show')) {
                            loadAging();
                        }
                    } else {
                        showErrorModal(response.message);
                    }
                }, 'json');
            });
        });

        // FORM SUBMISSIONS
        $('#editPartForm').on('submit', function (e) {
            e.preventDefault();
            const formData = $(this).serialize();
            $.ajax({
                url: '../api/spareparts_inventory.php?action=edit_parts',
                method: 'POST',
                data: formData,
                success: response => {
                    if (response.success) {
                        $('#editPartModal').modal('hide');
                        showSuccessModal('Part updated successfully.');
                        loadInventory();
                    } else {
                        showErrorModal(response.message);
                    }
                }
            });
        });

        $('#editSaleForm').on('submit', function (e) {
            e.preventDefault();
            const btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');

            $.post('../api/spareparts_inventory.php?action=edit_sale', $(this).serialize(), response => {
                if (response.success) {
                    $('#editSaleModal').modal('hide');
                    showSuccessModal(response.message);
                    loadSales();
                } else {
                    showErrorModal(response.message);
                }
            }, 'json').always(() => btn.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i>Save Changes'));
        });

        // ADD STOCK LOGIC (Parts In)
        let partsInCart = [];
        $('#partSearchIn').on('input', function () {
            const query = $(this).val();
            if (query.length < 2) { $('#searchInResults').hide(); return; }
            $.get('../api/spareparts_inventory.php?action=search_inventory_parts', { term: query }, response => {
                let html = '';
                if (response.success && Array.isArray(response.data)) {
                    response.data.forEach(item => {
                        html += `<button type="button" class="list-group-item list-group-item-action add-to-in-cart" 
                            data-item='${JSON.stringify(item).replace(/'/g, "&apos;")}'>
                            <div class="d-flex justify-content-between">
                                <strong>${item.part_no}</strong>
                                <small class="text-muted">${item.current_branch}</small>
                            </div>
                            <div class="small">${item.description}</div>
                        </button>`;
                    });
                }
                $('#searchInResults').html(html).show();
            }, 'json');
        });

        $(document).on('click', '.add-to-in-cart', function () {
            const item = $(this).data('item');
            addPartToInCart(item);
            $('#searchInResults').hide();
            $('#partSearchIn').val('');
        });

        $('#addManualPartBtn').on('click', function () {
            const part = {
                brand: $('#manual_brand').val(),
                part_no: $('#manual_part_no').val(),
                description: $('#manual_desc').val(),
                quantity: parseInt($('#manual_qty').val()),
                cost: parseFloat($('#manual_cost').val() || 0),
                price: parseFloat($('#manual_price').val() || 0),
                current_stock: 0,
                is_manual: true
            };
            if (!part.part_no || !part.description) { showErrorModal('Part No and Description are required.'); return; }
            addPartToInCart(part);
            $('#manual_brand, #manual_part_no, #manual_desc, #manual_cost, #manual_price').val('');
            $('#manual_qty').val(1);
        });

        function addPartToInCart(item) {
            const existing = partsInCart.find(p => p.part_no === item.part_no && p.current_branch === item.current_branch);
            if (existing) {
                existing.quantity += (item.quantity || 1);
            } else {
                partsInCart.push({
                    ...item,
                    quantity: item.quantity || 1
                });
            }
            renderPartsInCart();
        }

        $(document).on('click', '.remove-in-cart', function () {
            const index = $(this).data('index');
            partsInCart.splice(index, 1);
            renderPartsInCart();
        });

        function renderPartsInCart() {
            const tbody = $('#partsToAddList');
            tbody.empty();
            let totalQty = 0, totalCost = 0, totalPrice = 0;

            if (partsInCart.length === 0) {
                tbody.html('<tr id="emptyAddListRow"><td colspan="7" class="text-center text-muted py-5">Cart is empty</td></tr>');
            } else {
                partsInCart.forEach((item, index) => {
                    totalQty += item.quantity;
                    totalCost += (item.quantity * item.cost);
                    totalPrice += (item.quantity * item.price);
                    tbody.append(`
                        <tr>
                            <td class="text-center">${index + 1}</td>
                            <td>${item.brand || 'N/A'}</td>
                            <td><strong>${item.part_no}</strong><br><small>${item.description}</small></td>
                            <td class="text-center">${item.quantity}</td>
                            <td class="text-end">${formatCurrency(item.cost)}</td>
                            <td class="text-end">${formatCurrency(item.price)}</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-in-cart" data-index="${index}"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                    `);
                });
            }
            $('#addTotalQty').text(totalQty);
            $('#addTotalCost').text(formatCurrency(totalCost));
            $('#addTotalPrice').text(formatCurrency(totalPrice));
            $('#itemCountBadge').text(`${partsInCart.length} Items`);
        }

        $('#addPartsInForm').on('submit', function (e) {
            e.preventDefault();
            if (partsInCart.length === 0) { showErrorModal('Please add at least one item.'); return; }

            const btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Saving...');

            $.ajax({
                url: '../api/spareparts_inventory.php?action=add_multiple_parts_in',
                method: 'POST',
                data: {
                    items: JSON.stringify(partsInCart.map(p => ({
                        brand: p.brand,
                        part_no: p.part_no,
                        description: p.description,
                        quantity: p.quantity,
                        cost: p.cost,
                        price: p.price,
                        is_manual: p.is_manual || false
                    }))),
                    date_in: $('#add_date').val(),
                    invoice_no: $('#add_invoice_no').val() || '',
                    reference_no: $('#add_reference').val() || '',
                    supplier_source: $('#supplier_source').val() || 'HEADOFFICE'
                },
                success: response => {
                    if (response.success) {
                        $('#addPartsInModal').modal('hide');
                        showSuccessModal('Inventory updated successfully.');
                        partsInCart = [];
                        renderPartsInCart();
                        loadInventory();
                    } else {
                        showErrorModal(response.message);
                    }
                },
                complete: () => {
                    btn.prop('disabled', false).html('Save Stock (IN)');
                }
            });
        });

        // TRANSFER LOGIC
        let transferCart = [];
        $('#transferPartSearchInput').on('input', function () {
            const query = $(this).val();
            if (query.length < 2) { $('#transferPartSearchResults').hide(); return; }
            $.get('../api/spareparts_inventory.php?action=search_inventory_parts', { term: query }, response => {
                let html = '';
                if (response.success && Array.isArray(response.data)) {
                    response.data.forEach(item => {
                        html += `<button type="button" class="list-group-item list-group-item-action add-to-transfer-cart" 
                            data-item='${JSON.stringify(item).replace(/'/g, "&apos;")}'>
                            <div class="d-flex justify-content-between">
                                <strong>${item.part_no}</strong>
                                <small class="text-muted">${item.current_stock} in ${item.current_branch}</small>
                            </div>
                            <div class="small">${item.description}</div>
                        </button>`;
                    });
                }
                $('#transferPartSearchResults').html(html).show();
            }, 'json');
        });

        $(document).on('click', '.add-to-transfer-cart', function () {
            const item = $(this).data('item');
            addPartToTransferCart(item);
            $('#transferPartSearchResults').hide();
            $('#transferPartSearchInput').val('');
        });

        function addPartToTransferCart(item) {
            const existing = transferCart.find(p => p.id === item.id);
            if (existing) {
                if (existing.quantity < item.current_stock) existing.quantity++;
            } else {
                transferCart.push({ ...item, quantity: 1 });
            }
            renderTransferCart();
        }

        $(document).on('click', '.remove-transfer-item', function () {
            const index = $(this).data('index');
            transferCart.splice(index, 1);
            renderTransferCart();
        });

        function renderTransferCart() {
            const tbody = $('#partsToTransferList');
            tbody.empty();
            if (transferCart.length === 0) {
                tbody.html('<tr id="emptyTransferListRow"><td colspan="3" class="text-center text-muted p-5">Cart is empty</td></tr>');
            } else {
                transferCart.forEach((item, index) => {
                    tbody.append(`
                        <tr>
                            <td class="ps-3">
                                <strong>${item.part_no}</strong><br>
                                <small class="text-muted">${item.description}</small>
                            </td>
                            <td>
                                <input type="number" class="form-control form-control-sm text-center transfer-qty-input" 
                                    data-index="${index}" value="${item.quantity}" min="1" max="${item.current_stock}">
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-transfer-item" data-index="${index}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `);
                });
            }
        }

        $(document).on('change', '.transfer-qty-input', function () {
            const index = $(this).data('index');
            const qty = parseInt($(this).val());
            const max = parseInt($(this).attr('max'));
            if (qty > max) $(this).val(max);
            transferCart[index].quantity = parseInt($(this).val());
        });

        $('#transferPartsForm').on('submit', function (e) {
            e.preventDefault();
            if (transferCart.length === 0) { showErrorModal('Add items to transfer first.'); return; }
            if (!$('#to_branch').val()) { showErrorModal('Please select a destination branch.'); return; }

            const btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true).text('Processing...');

            $.post('../api/spareparts_inventory.php?action=transfer_multiple_parts', {
                transfer_date: $('#transfer_date').val(),
                to_branch: $('#to_branch').val(),
                items: JSON.stringify(transferCart.map(p => ({
                    part_no: p.part_no,
                    description: p.description,
                    quantity: p.quantity
                })))
            }, response => {
                if (response.success) {
                    $('#transferPartsModal').modal('hide');
                    showSuccessModal('Transfer initiated successfully.');
                    transferCart = [];
                    renderTransferCart();
                    loadInventory();
                } else {
                    showErrorModal(response.message);
                }
            }, 'json').always(() => btn.prop('disabled', false).text('Initiate Transfer'));
        });

        // INCOMING TRANSFER ACTIONS
        let currentViewingTransferId = null;
        $(document).on('click', '.view-transfer-btn, .view-global-transfer-btn', function () {
            const id = $(this).data('id');
            currentViewingTransferId = id;
            $('#transferDetailsBody').html('<div class="text-center p-4"><div class="spinner-border text-primary"></div></div>');
            $('#viewTransferDetailsModal').modal('show');

            $.get('../api/spareparts_inventory.php?action=get_transfer_details', { id: id }, response => {
                if (response.success) {
                    renderTransferDetails(response.data);
                } else {
                    showErrorModal(response.message);
                    $('#viewTransferDetailsModal').modal('hide');
                }
            }, 'json');
        });

        function renderTransferDetails(data) {
            let itemsHtml = '';
            data.items.forEach(item => {
                itemsHtml += `
                    <tr>
                        <td>${item.part_no}<br><small>${item.description}</small></td>
                        <td class="text-center">${item.quantity}</td>
                    </tr>`;
            });

            $('#transferDetailsBody').html(`
                <div class="row mb-3">
                    <div class="col-6"><strong>From:</strong> ${data.from_branch}</div>
                    <div class="col-6 text-end"><strong>To:</strong> ${data.to_branch}</div>
                    <div class="col-6 text-muted small">Date: ${data.transfer_date}</div>
                    <div class="col-6 text-end"><span class="badge bg-primary">${data.status}</span></div>
                </div>
                <table class="table table-sm table-bordered">
                    <thead class="table-light"><tr><th>Item</th><th class="text-center">Qty</th></tr></thead>
                    <tbody>${itemsHtml}</tbody>
                </table>
            `);

            // Only show actions for In-Transit transfers to the current branch
            if (data.status === 'In-Transit') {
                $('#rejectTransferBtn, #confirmReceiveBtn').removeClass('d-none');
            } else {
                $('#rejectTransferBtn, #confirmReceiveBtn').addClass('d-none');
            }
        }

        $('#confirmReceiveBtn').on('click', function () {
            showConfirmModal('Are you sure you want to receive these items?', function () {
                $.post('../api/spareparts_inventory.php?action=accept_transfer', { id: currentViewingTransferId }, response => {
                    if (response.success) {
                        $('#viewTransferDetailsModal').modal('hide');
                        showSuccessModal('Transfer accepted and inventory updated.');
                        loadInventory();
                        loadTransfers();
                        loadIncomingTransfers();
                    } else {
                        showErrorModal(response.message);
                    }
                }, 'json');
            });
        });

        $('#rejectTransferBtn').on('click', function () {
            const reason = prompt('Please provide a reason for rejection:');
            if (reason === null) return;
            $.post('../api/spareparts_inventory.php?action=reject_transfer', { id: currentViewingTransferId, reason: reason }, response => {
                if (response.success) {
                    $('#viewTransferDetailsModal').modal('hide');
                    showSuccessModal('Transfer rejected.');
                    loadTransfers();
                    loadIncomingTransfers();
                } else {
                    showErrorModal(response.message);
                }
            }, 'json');
        });

        $(document).on('click', '.cancel-transfer-btn', function () {
            const id = $(this).data('id');
            showConfirmModal('Cancel this transfer? Inventory will be returned to source.', function () {
                $.post('../api/spareparts_inventory.php?action=delete_transfer', { id: id }, response => {
                    if (response.success) {
                        showSuccessModal('Transfer cancelled.');
                        loadInventory();
                        loadTransfers();
                    } else {
                        showErrorModal(response.message);
                    }
                }, 'json');
            });
        });

        // POS LOGIC (Sales Out)
        $('#out_customer_name').on('input', function () {
            const val = $(this).val().toLowerCase().trim();
            const resultsBox = $('#saleCustomerSearchResults');
            resultsBox.empty();

            if (!val) {
                resultsBox.hide();
                return;
            }

            $.get('../api/spareparts_inventory.php?action=search_unique_customers', { term: val }, response => {
                if (response.success && response.data.length > 0) {
                    let html = '';
                    response.data.forEach(item => {
                        // Assuming the API returns an array of strings
                        const custName = typeof item === 'string' ? item : (item.customer_name || '');
                        if (!custName) return;
                        html += `<button type="button" class="list-group-item list-group-item-action fw-bold fill-sale-customer" 
                                    data-name="${escapeHtml(custName)}">
                                    <i class="bi bi-person text-secondary me-2"></i>${escapeHtml(custName)}
                                 </button>`;
                    });
                    resultsBox.html(html).show();
                } else {
                    resultsBox.hide();
                }
            }, 'json');
        });

        $(document).on('click', '.fill-sale-customer', function () {
            $('#out_customer_name').val($(this).data('name'));
            $('#saleCustomerSearchResults').hide();
        });

        $(document).on('click', function (e) {
            if (!$(e.target).closest('#out_customer_name, #saleCustomerSearchResults').length) {
                $('#saleCustomerSearchResults').hide();
            }
        });

        $('#out_transaction_type').on('change', function () {
            const label = $('label[for="out_customer_name"]');
            if ($(this).val() === 'cash') {
                $('#out_customer_name').removeAttr('required');
                label.html('Customer Name <small class="text-muted fw-normal">(Optional for Cash)</small>');
            } else {
                $('#out_customer_name').prop('required', true);
                label.html('Customer Name <span class="text-danger">*</span>');
            }
        });

        $('#sellPartsOutModal').on('show.bs.modal', function () {
            setTimeout(() => {
                $('#out_transaction_type').trigger('change');
            }, 50);
        });

        // saleCart is already declared at the top of the file
        $('#salePartSearchInput').on('input', function () {
            const query = $(this).val();
            if (query.length < 2) { $('#salePartSearchResults').hide(); return; }
            $.get('../api/spareparts_inventory.php?action=search_inventory_parts', { term: query }, response => {
                let html = '';
                if (response.success && Array.isArray(response.data)) {
                    response.data.forEach(item => {
                        html += `<button type="button" class="list-group-item list-group-item-action add-to-sale-cart" 
                            data-item='${JSON.stringify(item).replace(/'/g, "&apos;")}'>
                            <div class="d-flex justify-content-between">
                                <strong>${item.part_no}</strong>
                                <small class="text-muted">₱${formatCurrency(item.price)}</small>
                            </div>
                            <div class="small">${item.description} (${item.current_stock} available)</div>
                        </button>`;
                    });
                }
                $('#salePartSearchResults').html(html).show();
            }, 'json');
        });

        $(document).on('click', '.add-to-sale-cart', function () {
            const item = $(this).data('item');
            addPartToSaleCart(item);
            $('#salePartSearchResults').hide();
            $('#salePartSearchInput').val('');
        });

        function addPartToSaleCart(item) {
            const existing = saleCart.find(p => p.id === item.id);
            if (existing) {
                if (existing.quantity < item.current_stock) existing.quantity++;
            } else {
                saleCart.push({ ...item, quantity: 1 });
            }
            renderSaleCart();
        }

        $(document).on('click', '.remove-sale-item', function () {
            const index = $(this).data('index');
            saleCart.splice(index, 1);
            renderSaleCart();
        });

        function renderSaleCart() {
            const tbody = $('#partsForSaleList');
            tbody.empty();
            let grandTotal = 0;

            if (saleCart.length === 0) {
                tbody.html('<tr id="emptySaleListRow"><td colspan="5" class="text-center text-muted p-4">Cart is empty</td></tr>');
            } else {
                saleCart.forEach((item, index) => {
                    const subtotal = item.quantity * item.price;
                    grandTotal += subtotal;
                    tbody.append(`
                        <tr>
                            <td><strong>${item.part_no}</strong><br><small>${item.description}</small></td>
                            <td class="text-center">
                                <input type="number" class="form-control form-control-sm text-center sale-qty-input" 
                                    data-index="${index}" value="${item.quantity}" min="1" max="${item.current_stock}">
                            </td>
                            <td class="text-end">${formatCurrency(item.price)}</td>
                            <td class="text-end fw-bold">${formatCurrency(subtotal)}</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-sale-item" data-index="${index}">
                                    <i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                    `);
                });
            }
            $('#pos-grand-total').text('₱' + formatCurrency(grandTotal));
        }

        $(document).on('change', '.sale-qty-input', function () {
            const index = $(this).data('index');
            const qty = parseInt($(this).val());
            const max = parseInt($(this).attr('max'));
            if (qty > max) $(this).val(max);
            saleCart[index].quantity = parseInt($(this).val());
            renderSaleCart();
        });

        $('#sellPartsOutForm').on('submit', function (e) {
            e.preventDefault();
            if (saleCart.length === 0) { showErrorModal('Add items to sale first.'); return; }

            const btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true).text('Confirming...');

            const saleData = {
                or_number: $('#out_or_number').val(),
                customer_name: $('#out_customer_name').val(),
                date: $('#out_date').val(),
                transaction_type: $('#out_transaction_type').val(),
                items: JSON.stringify(saleCart.map(p => ({ id: p.id, part_no: p.part_no, description: p.description, quantity: p.quantity, price: p.price })))
            };

            $.post('../api/spareparts_inventory.php?action=sell_multiple_parts_out', saleData, response => {
                if (response.success) {
                    $('#sellPartsOutModal').modal('hide');
                    showSuccessModal('Sale recorded successfully.');
                    saleCart = [];
                    renderSaleCart();
                    loadDashboardStats();
                    loadInventory();
                    loadSales();
                } else {
                    showErrorModal(response.message);
                }
            }, 'json').always(() => btn.prop('disabled', false).html('<i class="bi bi-check-circle me-2"></i>Confirm Sale'));
        });

        // PAYMENT LOGIC
        $(document).on('click', '.record-payment-btn', function () {
            const id = $(this).data('id');
            const customer = $(this).data('customer');
            const branch = $(this).data('branch');

            if (id) {
                // From Sales Table
                const sale = salesData.find(s => s.id == id);
                if (!sale) return;
                $('#payment_sale_id').val(sale.id);
                $('#payment_customer_name').val(sale.customer_name);
                $('#payment_or_number').val(sale.or_number);
                $('#payment_amount').val(sale.balance || 0);
                $('#payment_branch').val(sale.branch || '');
                $('#balance_info').text(`Outstanding Balance (OR# ${sale.or_number}): ₱${formatCurrency(sale.balance || 0)}`);
            } else if (customer) {
                // From Aging View
                const balRaw = $(this).closest('tr').find('td:nth-child(5)').text().replace('₱', '').replace(/,/g, '');
                const bal = parseFloat(balRaw) || 0;

                $('#payment_sale_id').val('');
                $('#payment_customer_name').val(customer);
                $('#payment_or_number').val(''); // Empty OR means distribute by customer
                $('#payment_amount').val(bal);
                $('#payment_branch').val(branch);
                $('#balance_info').text(`Total Outstanding Balance (${customer}): ₱${formatCurrency(bal)}`);

                // If it's from the Aging modal, close it first
                if ($('#paymentsAgingModal').hasClass('show')) {
                    $('#paymentsAgingModal').modal('hide');
                }
            } else {
                return;
            }

            $('#recordPaymentModal').modal('show');
        });

        // Customer Search Autocomplete Logic
        $('#payment_customer_search').on('input', function () {
            const val = $(this).val().toLowerCase().trim();
            const resultsBox = $('#customerSearchResults');
            resultsBox.empty();

            if (!val) return;

            // Filter unique active customers from aging data
            const matches = paymentsAgingData.filter(item =>
                item.customer_name.toLowerCase().includes(val) ||
                (item.or_number && item.or_number.toLowerCase().includes(val))
            ).slice(0, 10); // Limit to 10 results

            if (matches.length === 0) {
                resultsBox.append('<div class="list-group-item text-muted">No matching customers found with a balance.</div>');
                return;
            }

            // Create unique customer list to avoid duplicates if they have multiple branches
            const uniqueCustomers = [];
            matches.forEach(item => {
                if (!uniqueCustomers.find(c => c.customer_name === item.customer_name && c.branch === item.branch)) {
                    uniqueCustomers.push(item);
                }
            });

            uniqueCustomers.forEach(item => {
                resultsBox.append(`
                    <button type="button" class="list-group-item list-group-item-action fw-bold select-customer-btn" 
                        data-name="${escapeHtml(item.customer_name)}" 
                        data-branch="${escapeHtml(item.branch)}"
                        data-balance="${item.total_balance}">
                        ${escapeHtml(item.customer_name)} 
                        <span class="badge bg-secondary ms-2">${escapeHtml(item.branch)}</span>
                        <div class="small text-danger fw-bold mt-1">Balance: ₱${formatCurrency(item.total_balance)}</div>
                    </button>
                `);
            });
        });

        // Handle customer selection from search results
        $(document).on('click', '.select-customer-btn', function () {
            const name = $(this).data('name');
            const branch = $(this).data('branch');
            const balance = parseFloat($(this).data('balance'));

            $('#payment_customer_search').val(name);
            $('#customerSearchResults').empty();

            $('#payment_sale_id').val('');
            $('#payment_or_number').val('');
            $('#payment_customer_name').val(name);
            $('#payment_amount').val(balance);
            $('#payment_branch').val(branch);
            $('#balance_info').text(`Total Outstanding Balance (${name}): ₱${formatCurrency(balance)}`);
        });

        // Clear modal when manually opened
        $('button[data-bs-target="#recordPaymentModal"]').on('click', function () {
            // Fetch aging data if not yet loaded
            if (paymentsAgingData.length === 0) {
                $.get('../api/spareparts_inventory.php?action=get_aging_summary&branch=All', response => {
                    const res = typeof response === 'string' ? JSON.parse(response) : response;
                    if (res && res.success && res.data) {
                        paymentsAgingData = res.data;
                    }
                });
            }

            $('#payment_customer_search').val('').prop('disabled', false).parent().removeClass('d-none');
            $('#customerSearchResults').empty();

            $('#payment_sale_id').val('');
            $('#payment_customer_name').val('');
            $('#payment_or_number').val('');
            $('#payment_amount').val('');
            $('#payment_branch').val('');
            $('#payment_receipt_no').val('');
            $('#balance_info').text('');
        });

        $('#recordPaymentForm').on('submit', function (e) {
            e.preventDefault();
            const btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true).text('Recording...');
            $.post('../api/spareparts_inventory.php?action=record_payment', $(this).serialize(), response => {
                if (response.success) {
                    $('#recordPaymentModal').modal('hide');
                    showSuccessModal('Payment recorded successfully.');

                    // Reset fields
                    $('#payment_details_container').addClass('d-none');
                    $('.payment-detail-field').addClass('d-none').find('input').val('');

                    loadSales();
                    loadPayments();
                } else {
                    showErrorModal(response.message);
                }
            }, 'json').always(() => btn.prop('disabled', false).text('Record Payment'));
        });

        // Payment Method Change Logic
        $('#payment_method').on('change', function () {
            const method = $(this).val();
            const container = $('#payment_details_container');
            container.addClass('d-none');
            $('.payment-detail-field').addClass('d-none').find('input').val('');

            if (method === 'Check') {
                container.removeClass('d-none');
                $('.check-field').removeClass('d-none');
            } else if (method === 'Bank Transfer') {
                container.removeClass('d-none');
                $('.bank-transfer-field').removeClass('d-none');
            }
        });

        $('#edit_payment_method').on('change', function () {
            const method = $(this).val();
            const container = $('#edit_payment_details_container');
            container.addClass('d-none');
            $('.edit-payment-detail-field').addClass('d-none');

            if (method === 'Check') {
                container.removeClass('d-none');
                $('.edit-check-field').removeClass('d-none');
            } else if (method === 'Bank Transfer') {
                container.removeClass('d-none');
                $('.edit-bank-transfer-field').removeClass('d-none');
            }
        });

        // Edit Payment Logic
        $(document).on('click', '.edit-payment-btn', function () {
            const id = $(this).data('id');
            const p = paymentsData.find(item => item.id == id);
            if (!p) return;

            $('#edit_payment_id').val(p.id);
            $('#edit_payment_customer').val(p.customer_name);
            $('#edit_payment_receipt_no').val(p.or_number);

            // Format date for the input (YYYY-MM-DD)
            const dateObj = new Date(p.transaction_date);
            const dateString = dateObj.getFullYear() + '-' + String(dateObj.getMonth() + 1).padStart(2, '0') + '-' + String(dateObj.getDate()).padStart(2, '0');
            $('#edit_payment_date').val(dateString);

            $('#edit_payment_amount').val(p.amount);
            $('#edit_payment_method').val(p.payment_method || 'Cash').trigger('change');

            // Populate extra fields
            $('#edit_payment_check_number').val(p.check_number || '');
            $('#edit_payment_bank_name').val(p.bank_name || '');
            $('#edit_payment_reference_number').val(p.reference_number || '');

            $('#editPaymentModal').modal('show');
        });

        $('#editPaymentForm').on('submit', function (e) {
            e.preventDefault();
            const btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');

            $.post('../api/spareparts_inventory.php?action=edit_payment', $(this).serialize(), response => {
                if (response.success) {
                    $('#editPaymentModal').modal('hide');
                    showSuccessModal(response.message);
                    loadPayments();
                    if ($('#paymentsAgingModal').hasClass('show')) {
                        loadAging();
                    }
                } else {
                    showErrorModal(response.message);
                }
            }, 'json').always(() => btn.prop('disabled', false).html('<i class="bi bi-save me-2"></i>Save Changes'));
        });

        $(document).on('click', '.view-receipt-btn', function () {
            const id = $(this).data('id');
            const p = paymentsData.find(item => item.id == id);
            if (!p) return;

            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                    <head>
                        <title>Payment Receipt - ${p.or_number}</title>
                        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'>
                        <style>
                            body { font-family: 'Calibri', 'Arial', sans-serif; padding: 40px; }
                            .receipt-box { border: 2px solid #000; padding: 30px; max-width: 600px; margin: 0 auto; }
                            .header { border-bottom: 2px solid #000; margin-bottom: 20px; padding-bottom: 10px; }
                        </style>
                    </head>
                    <body>
                        <div class="receipt-box">
                            <div class="header text-center">
                                <h3></h3>
                                <h5>OFFICIAL RECEIPT</h5>
                            </div>
                            <div class="row mb-3">
                                <div class="col-6"><strong>OR #:</strong> ${p.or_number}</div>
                                <div class="col-6 text-end"><strong>Date:</strong> ${p.transaction_date}</div>
                            </div>
                            <div class="mb-3">
                                <strong>Customer:</strong> ${p.customer_name}
                            </div>
                            <div class="mb-4">
                                <strong>Branch:</strong> ${p.from_location}
                            </div>
                            <div class="text-center py-4 bg-light mb-4">
                                <h2 class="mb-0">Amount Paid: ₱${formatCurrency(p.amount)}</h2>
                            </div>
                            <div class="mt-5 pt-5 row">
                                <div class="col-6 text-center">
                                    <div class="border-bottom mx-auto w-75 mb-1"></div>
                                    <div class="small">Received By</div>
                                </div>
                                <div class="col-6 text-center">
                                    <div class="border-bottom mx-auto w-75 mb-1"></div>
                                    <div class="small">Customer Signature</div>
                                </div>
                            </div>
                        </div>
                        <script>window.onload = function() { window.print(); window.close(); }</script>
                    </body>
                </html>
            `);
            printWindow.document.close();
        });

        // REPORT GENERATION LOGIC
        function toggleReportDateFields(prefix) {
            $(`#${prefix}_report_period`).on('change', function () {
                const period = $(this).val();
                $(`#${prefix}_report_date_container, #${prefix}_report_month_container, #${prefix}_report_year_container, #${prefix}_report_custom_container`).addClass('d-none');

                if (period === 'daily') $(`#${prefix}_report_date_container`).removeClass('d-none');
                else if (period === 'monthly') $(`#${prefix}_report_month_container`).removeClass('d-none').find('input').attr('required', true);
                else if (period === 'yearly') $(`#${prefix}_report_year_container`).removeClass('d-none').find('input').attr('required', true);
                else if (period === 'custom') $(`#${prefix}_report_custom_container`).removeClass('d-none').find('input').attr('required', true);
            });
        }

        toggleReportDateFields('inv');
        toggleReportDateFields('t');
        toggleReportDateFields('sales');
        toggleReportDateFields('payment');

        // Inventory Report Preview
        $('#btnGenerateReport').on('click', function () {
            const formData = {
                report_type: $('#inv_report_type').val(),
                period: $('#inv_report_period').val(),
                date_value: getPeriodValue('inv'),
                branch: $('#inv_report_branch').val(),
                category: $('#inv_report_filter_category').val(),
                brand: $('#inv_report_filter_brand').val(),
                part_no: $('#inv_report_filter_part_no').val(),
                low_stock: $('#inv_report_filter_low_stock').is(':checked') ? 1 : 0
            };

            let title = $('#inv_report_type option:selected').text();
            loadReportPreview('generate_inventory_report', formData, title);
        });

        // Sales Report Preview
        $('#btnGenerateSalesReport').on('click', function () {
            const formData = {
                period: $('#sales_report_period').val(),
                date_value: getPeriodValue('sales'),
                branch: $('#sales_report_branch').val(),
                type: $('#sales_report_type').val(),
                brand: $('#sales_report_filter_brand').val()
            };
            loadReportPreview('generate_sales_summary_report', formData, 'Sales Summary Report');
        });

        // Payment Report Preview
        $('#btnGeneratePaymentReport').on('click', function () {
            const formData = {
                period: $('#payment_report_period').val(),
                date_value: getPeriodValue('payment'),
                branch: $('#payment_report_branch').val(),
                customer: $('#payment_report_filter_customer').val()
            };
            loadReportPreview('get_payments_list', formData, 'Payments Report');
        });

        // Transfer Report Preview
        $('#btnGenerateTransferReport').on('click', function () {
            const formData = {
                report_type: $('#t_report_type').val(),
                period: $('#t_report_period').val(),
                date_value: getPeriodValue('t'),
                branch: $('#t_report_branch').val()
            };
            loadReportPreview('generate_transfer_report', formData, 'Transfer Report');
        });

        // EXPORT HANDLERS
        $('#finalizeInventoryExportBtn').on('click', () => exportToCSV(currentReportData, currentReportTitle));
        $('#finalizeInventoryExportExcelBtn').on('click', () => exportToExcel(currentReportData, currentReportTitle));
        $('#finalizeInventoryExportPdfBtn').on('click', () => exportToPDF(currentReportData, currentReportTitle));
        $('#finalizeInventoryPrintBtn').on('click', () => printReport(currentReportData, currentReportTitle));
        // Initial badge check with a small delay to ensure DOM and BS are ready
        if (!window.location.pathname.includes('admin_spareparts.php')) {
            setTimeout(updateIncomingBadge, 500);
            setInterval(updateIncomingBadge, 30000); // Check every 30 seconds
        }


    }

    let hasShownAlert = false;
    function updateIncomingBadge() {
        console.log("Checking incoming transfers... isBranchPage:", isBranchPage);
        $.get('../api/spareparts_inventory.php?action=get_incoming_count', response => {
            if (response.success && response.count > 0) {
                console.log("Found incoming transfers:", response.count);
                $('#incoming-badge').text(response.count).removeClass('d-none');

                // Auto-show alert modal on first load if transfers found
                if (!hasShownAlert) {
                    const modalEl = document.getElementById('incomingTransferAlertModal');
                    console.log("Checking for alert modal existence:", !!modalEl);
                    if (modalEl) {
                        $('#alert-incoming-count').text(response.count);
                        try {
                            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                                // Cleanup any stale backdrops first
                                $('.modal-backdrop').remove();
                                $('body').removeClass('modal-open').css('padding-right', '');

                                const alertModal = bootstrap.Modal.getOrCreateInstance(modalEl);
                                alertModal.show();
                                hasShownAlert = true;
                            } else {
                                $(modalEl).modal('show');
                                hasShownAlert = true;
                            }
                        } catch (err) {
                            console.error("Modal initiation failed, trying jQuery fallback:", err);
                            $(modalEl).modal('show');
                            hasShownAlert = true;
                        }
                    }
                }
            } else {
                $('#incoming-badge').addClass('d-none');
            }
        }, 'json');
    }

    // Modal populate logic for Incoming Transfers
    function loadIncomingTransferDetails() {
        console.log("Loading incoming transfer details...");
        $('#incomingTransferDetailsBody').html('<tr><td colspan="9" class="text-center py-4"><div class="spinner-border text-primary"></div></td></tr>');

        $.get('../api/spareparts_inventory.php?action=get_incoming_transfers_detailed', response => {
            console.log("Incoming details response:", response);
            if (response.success) {
                renderIncomingTransferDetails(response.data);
            } else {
                console.error("Failed to load incoming details:", response.message);
                $('#incomingTransferDetailsBody').html(`<tr><td colspan="9" class="text-center text-danger py-4">${response.message}</td></tr>`);
            }
        }).fail(xhr => {
            console.error("API Error loading incoming details:", xhr.responseText);
            $('#incomingTransferDetailsBody').html('<tr><td colspan="9" class="text-center text-danger py-4">Error connecting to server.</td></tr>');
        });
    }

    function renderIncomingTransferDetails(data) {
        let html = '';
        if (!data || data.length === 0) {
            html = '<tr><td colspan="8" class="text-center py-4 text-muted">No pending transfers found.</td></tr>';
        } else {
            data.forEach(t => {
                html += `
                    <tr>
                        <td class="ps-4">
                            <input type="checkbox" class="form-check-input incoming-checkbox" data-id="${t.id}" data-item-id="${t.item_id}">
                        </td>
                        <td><div class="fw-bold text-dark-green">${t.part_no}</div></td>
                        <td>${t.description}</td>
                        <td>${t.brand}</td>
                        <td class="text-center fw-bold">${t.qty}</td>
                        <td class="text-center">${t.transfer_date}</td>
                        <td class="text-center"><span class="badge bg-light text-dark-green border border-dark-green">${t.from_branch}</span></td>
                        <td class="text-center">
                            <span class="badge bg-warning text-dark">Pending</span>
                        </td>
                    </tr>
                `;
            });
        }
        $('#incomingTransferDetailsBody').html(html);
        updateIncomingBatchButtons();
    }

    function updateIncomingBatchButtons() {
        const selectedCount = $('.incoming-checkbox:checked').length;
        $('#incomingSelectedNum').text(selectedCount);

        if (selectedCount > 0) {
            $('#incomingSelectionCount').removeClass('d-none');
            $('#batchAcceptIncomingBtn, #batchRejectIncomingBtn').removeClass('d-none');
        } else {
            $('#incomingSelectionCount').addClass('d-none');
            $('#batchAcceptIncomingBtn, #batchRejectIncomingBtn').addClass('d-none');
        }
    }

    function updateInventoryBatchButtons() {
        const selectedCount = $('.inventory-checkbox:checked').length;
        $('#selectedCount').text(selectedCount);
        if (selectedCount > 0) {
            $('#inventoryBatchBar').css('display', 'flex');
        } else {
            $('#inventoryBatchBar').css('display', 'none');
        }
    }

    function updateSalesBatchButtons() {
        const selectedCount = $('.sales-checkbox:checked').length;
        $('#salesSelectedCount').text(selectedCount);
        if (selectedCount > 0) {
            $('#salesBatchBar').css('display', 'flex');
        } else {
            $('#salesBatchBar').css('display', 'none');
        }
    }

    // Event listeners for batch selections
    $(document).on('change', '.inventory-checkbox', updateInventoryBatchButtons);
    $(document).on('change', '.sales-checkbox', updateSalesBatchButtons);
    $(document).on('change', '.incoming-checkbox', updateIncomingBatchButtons);

    $('#selectAllInventory').on('change', function () {
        $('.inventory-checkbox').prop('checked', $(this).is(':checked'));
        updateInventoryBatchButtons();
    });

    $('#selectAllSales').on('change', function () {
        $('.sales-checkbox').prop('checked', $(this).is(':checked'));
        updateSalesBatchButtons();
    });

    // Event listeners for incoming transfers
    $(document).on('change', '.incoming-checkbox', updateIncomingBatchButtons);

    $('#selectAllIncoming').on('change', function () {
        $('.incoming-checkbox').prop('checked', this.checked);
        updateIncomingBatchButtons();
    });

    // Global Search (Find Tab)
    $('#searchPartBtn').on('click', function () {
        searchGlobalParts();
    });

    $('#searchPart').on('keypress', function (e) {
        if (e.which == 13) {
            searchGlobalParts();
        }
    });

    function searchGlobalParts() {
        const term = $('#searchPart').val().trim();
        if (term.length < 2) {
            showErrorModal('Please enter at least 2 characters to search.');
            return;
        }

        $('#partList').html('<div class="text-center p-5"><div class="spinner-border text-primary"></div><p class="mt-2">Searching across branches...</p></div>');

        $.get('../api/spareparts_inventory.php', { action: 'search_parts_global', term: term }, function (response) {
            if (response.success) {
                renderGlobalSearchResults(response.data);
            } else {
                $('#partList').html(`<div class="alert alert-danger">${response.message}</div>`);
            }
        }, 'json');
    }

    function renderGlobalSearchResults(data) {
        if (!data || data.length === 0) {
            $('#partList').html('<div class="text-center text-muted py-5"><i class="bi bi-search" style="font-size: 3rem;"></i><p class="mt-3">No matching parts found in any branch.</p></div>');
            return;
        }

        // Group by branch
        const grouped = {};
        data.forEach(item => {
            if (!grouped[item.current_branch]) grouped[item.current_branch] = [];
            grouped[item.current_branch].push(item);
        });

        let html = '<div class="row g-4">';
        for (const branch in grouped) {
            html += `
            <div class="col-12">
                <div class="card border-0 shadow-sm overflow-hidden mb-3">
                    <div class="card-header bg-dark-green text-white fw-bold py-2">
                        <i class="bi bi-geo-alt-fill me-2"></i>${branch}
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr class="bg-dark-green-light text-dark-green">
                                    <th>Part No</th>
                                    <th>Description</th>
                                    <th>Brand</th>
                                    <th class="text-center">Available Stock</th>
                                    <th class="text-end pe-4">Price</th>
                                </tr>
                            </thead>
                            <tbody>
        `;

            grouped[branch].forEach(item => {
                html += `
                <tr>
                    <td class="fw-bold text-dark-green" style="width:200px">${item.part_no}</td>
                    <td>${item.description}</td>
                    <td><span class="badge bg-light text-dark-green border border-dark-green">${item.brand}</span></td>
                    <td class="text-center">
                        <span class="badge ${item.current_stock > 10 ? 'bg-dark-green text-white' : 'bg-warning text-dark'} rounded-pill px-3">
                            ${item.current_stock}
                        </span>
                    </td>
                    <td class="text-end pe-4">₱${formatCurrency(item.price)}</td>
                </tr>
            `;
            });

            html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
        }
        html += '</div>';
        $('#partList').html(html);
    }

    $('#viewIncomingFromAlert').on('click', function () {
        $('#incomingTransferAlertModal').modal('hide');
        $('#viewIncomingTransferModal').modal('show');
        loadIncomingTransferDetails();
    });

    // Also trigger modal when clicking the transfer-in tab/button if applicable
    $('#transfer-in-tab, #incoming-transfer-tab').on('click', function (e) {
        // If it's a link or button that should show the modal
        if ($(this).attr('id') === 'transfer-in-tab' || $(this).attr('id') === 'incoming-transfer-tab') {
            $('#viewIncomingTransferModal').modal('show');
            loadIncomingTransferDetails();
        }
    });

    $(document).on('click', '#batchAcceptIncomingBtn', function () {
        console.log("Batch Accept clicked");
        const selectedIds = $('.incoming-checkbox:checked').map(function () { return $(this).data('id'); }).get();
        console.log("Selected IDs:", selectedIds);
        if (selectedIds.length === 0) {
            showErrorModal("Please select at least one item.");
            return;
        }

        console.log("Confirmed Batch Accept. Sending request...");
        $.post('../api/spareparts_inventory.php?action=batch_receive_transfers', { ids: selectedIds }, response => {
            console.log("Batch Accept Response:", response);
            if (response.success) {
                showSuccessModal(response.message);
                loadIncomingTransferDetails();
                updateIncomingBadge();
            } else {
                showErrorModal(response.message);
            }
        }, 'json').fail(xhr => {
            console.error("Batch Accept AJAX Error:", xhr.responseText);
            showErrorModal("System error while processing batch acceptance.");
        });
    });

    $(document).on('click', '#batchRejectIncomingBtn', function () {
        console.log("Batch Reject clicked");
        const selectedIds = $('.incoming-checkbox:checked').map(function () { return $(this).data('id'); }).get();
        console.log("Selected IDs for rejection:", selectedIds);
        if (selectedIds.length === 0) {
            showErrorModal("Please select at least one item to reject.");
            return;
        }

        console.log("Confirmed Batch Reject. Sending request...");
        $.post('../api/spareparts_inventory.php?action=batch_reject_transfers', { ids: selectedIds }, response => {
            console.log("Batch Reject Response:", response);
            if (response.success) {
                showSuccessModal(response.message);
                loadIncomingTransferDetails();
                updateIncomingBadge();
            } else {
                showErrorModal(response.message);
            }
        }, 'json').fail(xhr => {
            console.error("Batch Reject AJAX Error:", xhr.responseText);
            showErrorModal("System error while processing batch rejection.");
        });
    });

    function getPeriodValue(prefix) {
        const period = $(`#${prefix}_report_period`).val();
        if (period === 'daily') return $(`#${prefix}_report_date`).val();
        if (period === 'monthly') return $(`#${prefix}_report_month`).val();
        if (period === 'yearly') return $(`#${prefix}_report_year`).val();
        if (period === 'custom') {
            const start = $(`#${prefix}_report_date_start`).val();
            const end = $(`#${prefix}_report_date_end`).val();
            return start + ' to ' + end;
        }
        return '';
    }

    let currentReportData = [];
    let currentReportSummary = null;
    let currentReportTitle = '';
    let lastAction = '';
    let currentMatrixData = null;
    let currentBranches = null;
    let currentParts = null;
    let currentFullResponse = null;

    function loadReportPreview(action, data, title) {
        currentReportTitle = title;
        lastAction = action;
        $('#inventoryPreviewSubtitle').text(`${title} Details`);
        $('#inventoryPreviewTableBody').html('<tr><td colspan="10" class="text-center py-5"><div class="spinner-border text-dark-green"></div></td></tr>');
        $('#reportSummarySidebar').empty();
        $('#reportBrandSummaryArea').empty();
        $('#reportSummaryTabsArea').empty().addClass('d-none');
        $('#inventoryPreviewModal').modal('show');

        $.post(`../api/spareparts_inventory.php?action=${action}`, data, response => {
            if (response.success) {
                currentReportData = response.data;
                currentReportSummary = response.summary || null;
                currentMatrixData = response.matrix || null;
                currentBranches = response.branches || null;
                currentParts = response.parts || null;
                currentFullResponse = response;
                renderReportPreview(response.data, action, response.summary, response);
            } else {
                showErrorModal(response.message);
                $('#inventoryPreviewTableBody').html(`<tr><td colspan="10" class="text-center text-danger py-5"><i class="bi bi-exclamation-triangle-fill me-2"></i>Error: ${response.message}</td></tr>`);
            }
        }, 'json').fail(xhr => {
            console.error("Report Post Error:", xhr.responseText);
            showErrorModal("System error while generating report.");
            $('#inventoryPreviewTableBody').html(`<tr><td colspan="10" class="text-center text-danger py-5"><i class="bi bi-exclamation-triangle-fill me-2"></i>Failed to load report. Please check server logs.</td></tr>`);
        });
    }

    function getReportConfig(action, data = []) {
        let config = {
            title: currentReportTitle,
            headers: [],
            keys: [],
            formatters: {},
            showFooter: true
        };

        const invType = $('#inv_report_type').val();

        if (action === 'generate_inventory_report') {
            switch (invType) {
                case 'inventory_balance':
                    config.headers = ['Brand', 'Part No', 'Description', 'Category', 'Beg Bal', 'Stock In', 'Stock Out', 'End Bal'];
                    config.keys = ['brand', 'part_no', 'description', 'category', 'beginning_balance', 'inventory_in', 'inventory_out', 'ending_balance'];
                    break;
                case 'inventory_summary':
                    config.headers = ['Branch', 'Brand', 'Part No', 'Description', 'Category', 'Available Stocks'];
                    config.keys = ['branch', 'brand', 'part_no', 'description', 'category', 'available'];
                    break;
                case 'transferred_stocks':
                    config.headers = ['Date', 'Part No', 'Description', 'Brand', 'Category', 'Qty', 'To Branch', 'Status'];
                    config.keys = ['transaction_date', 'part_no', 'description', 'brand', 'category', 'quantity', 'to_location', 'status'];
                    break;
                case 'received_stocks':
                    config.headers = ['Date', 'Part No', 'Description', 'Brand', 'Category', 'Qty', 'From Branch', 'Status'];
                    config.keys = ['transaction_date', 'part_no', 'description', 'brand', 'category', 'quantity', 'from_location', 'status'];
                    break;
                case 'delivered_stocks':
                    config.headers = ['Date', 'Part No', 'Description', 'Brand', 'Category', 'Qty', 'Supplier', 'OR/Ref #'];
                    config.keys = ['transaction_date', 'part_no', 'description', 'brand', 'category', 'quantity', 'from_location', 'or_number'];
                    break;
                default: // Legacy
                    config.headers = ['Date', 'Branch', 'Part No', 'Description', 'Brand', 'Qty', 'Price', 'Total'];
                    config.keys = ['transaction_date', 'display_branch', 'part_no', 'description', 'brand', 'quantity', 'price', 'total_amount'];
                    config.formatters = { price: formatCurrency, total_amount: formatCurrency };
                    break;
            }
        } else {
            switch (action) {
                case 'generate_sales_summary_report':
                    config.headers = ['Date', 'Branch', 'Part No', 'Customer', 'OR #', 'Description', 'Brand', 'Qty', 'Price', 'Total'];
                    config.keys = ['transaction_date', 'from_location', 'part_no', 'customer_name', 'or_number', 'description', 'brand', 'quantity', 'price', 'total_amount'];
                    config.formatters = { price: formatCurrency, total_amount: formatCurrency };
                    break;
                case 'get_payments_list':
                    config.headers = ['Date', 'Customer', 'OR #', 'Branch', 'Amount'];
                    config.keys = ['transaction_date', 'customer_name', 'or_number', 'from_location', 'amount'];
                    config.formatters = { amount: formatCurrency };
                    break;
                case 'generate_transfer_report':
                    config.headers = ['Date', 'Ref #', 'From', 'To', 'Part No', 'Qty', 'Status'];
                    config.keys = ['transfer_date', 'transfer_number', 'from_branch', 'to_branch', 'part_no', 'quantity', 'status'];
                    config.showFooter = false;
                    break;
            }
        }
        return config;
    }

    function renderReportPreview(data, action, summary, response) {
        const thead = $('#inventoryPreviewHead');
        const tbody = $('#inventoryPreviewTableBody');
        const tfoot = $('#inventoryPreviewFoot');
        thead.empty(); tbody.empty(); tfoot.empty();
        $('#reportSummaryTabsArea').empty().addClass('d-none');

        const invType = $('#inv_report_type').val();
        if (action === 'generate_inventory_report' && invType === 'inventory_summary') {
            renderMatrixReport(response);
            return;
        }

        if (!data || data.length === 0) {
            tbody.html('<tr><td colspan="12" class="text-center py-4">No records found for the selected criteria.</td></tr>');
            return;
        }

        const config = getReportConfig(action, data);
        thead.append(`<tr>${config.headers.map(h => `<th>${h}</th>`).join('')}</tr>`);

        let totals = { quantity: 0, amount: 0 };

        data.forEach(row => {
            let tr = '<tr>';
            config.keys.forEach(key => {
                let val = row[key] !== undefined && row[key] !== null ? row[key] : '-';
                if (config.formatters[key]) val = config.formatters[key](val);
                if (key === 'display_branch' || key === 'from_location' || key === 'to_location') {
                    if (val !== '-') val = `<span class="badge bg-dark-green">${escapeHtml(val)}</span>`;
                }
                tr += `<td>${val}</td>`;
            });
            tr += '</tr>';
            tbody.append(tr);

            if (row.quantity) totals.quantity += Number(row.quantity);
            if (row.total_amount) totals.amount += Number(row.total_amount);
            else if (row.amount) totals.amount += Number(row.amount);
        });

        if (summary) {
            const isBalance = action === 'generate_inventory_report' && invType === 'inventory_balance';
            renderReportSidebar(summary, isBalance);
            renderBrandSummary(summary);
        }

        if (config.showFooter && !summary) { // Hide default footer if summary sidebar exists to avoid clutter
            const colSpan = config.headers.indexOf('Qty') !== -1 ? config.headers.indexOf('Qty') : config.headers.length - 1;
            let footerRow = `<tr class="bg-dark-green text-white"><td colspan="${colSpan}" class="text-end fw-bold text-white">TOTAL:</td>`;
            if (config.headers.includes('Qty')) {
                footerRow += `<td class="fw-bold">${totals.quantity}</td>`;
                const remaining = config.headers.length - colSpan - 2;
                if (remaining > 0) footerRow += `<td colspan="${remaining}"></td>`;
            }
            footerRow += `<td class="fw-bold text-end">${formatCurrency(totals.amount)}</td></tr>`;
            tfoot.append(footerRow);
        }
    }

    function renderMatrixReport(response, skipTabs = false) {
        const { matrix, branches, parts, summary } = response;

        if (!skipTabs) {
            renderReportTabs(parts, response);
        }

        const thead = $('#inventoryPreviewHead');
        const tbody = $('#inventoryPreviewTableBody');
        const tfoot = $('#inventoryPreviewFoot');
        thead.empty(); tbody.empty(); tfoot.empty();

        // Header: PART | BRANCH1 | BRANCH2 | ... | TOTAL
        let headRow = `<tr class="bg-dark-green text-white text-center">
            <th class="text-start">PART NO</th>`;
        branches.forEach(b => {
            headRow += `<th>${escapeHtml(b)}</th>`;
        });
        headRow += `<th>GRAND TOTAL</th></tr>`;
        thead.append(headRow);

        // Map data for easy lookup: matrixMap[part_no][branch] = {qty, value}
        const matrixMap = {};
        matrix.forEach(item => {
            if (!matrixMap[item.part_no]) matrixMap[item.part_no] = {};
            matrixMap[item.part_no][item.branch] = { qty: Number(item.total_qty), val: Number(item.total_value) };
        });

        // Rows
        let grandTotalQty = 0;
        let grandTotalVal = 0;
        const branchTotals = {}; // branch -> {qty, val}

        Object.keys(parts).forEach(part_no => {
            const description = parts[part_no];
            let row = `<tr><td><div class="fw-bold">${escapeHtml(part_no)}</div><div class="small text-muted">${escapeHtml(description)}</div></td>`;
            let partTotalQty = 0;
            let partTotalVal = 0;

            branches.forEach(branch => {
                const cell = matrixMap[part_no]?.[branch] || { qty: 0, val: 0 };
                const qtyStr = cell.qty > 0 ? cell.qty : '-';
                const valStr = cell.qty > 0 ? formatCurrency(cell.val) : '0.00';

                row += `<td class="text-center">
                    <div class="fw-bold">${qtyStr}</div>
                    <div class="small text-dark-green">${valStr}</div>
                </td>`;

                partTotalQty += cell.qty;
                partTotalVal += cell.val;

                if (!branchTotals[branch]) branchTotals[branch] = { qty: 0, val: 0 };
                branchTotals[branch].qty += cell.qty;
                branchTotals[branch].val += cell.val;
            });

            row += `<td class="text-center bg-dark-green-light fw-bold">
                <div>${partTotalQty}</div>
                <div class="small text-dark-green">${formatCurrency(partTotalVal)}</div>
            </td></tr>`;
            tbody.append(row);

            grandTotalQty += partTotalQty;
            grandTotalVal += partTotalVal;
        });

        // Footer: TOTAL | BRANCH_TOTALS ... | GRAND_TOTAL
        let footRow = `<tr class="bg-dark-green-light fw-bold">
            <td class="text-dark-green">TOTAL</td>`;
        branches.forEach(b => {
            const bt = branchTotals[b] || { qty: 0, val: 0 };
            footRow += `<td class="text-center text-dark-green">
                <div>${bt.qty}</div>
                <div class="small">${formatCurrency(bt.val)}</div>
            </td>`;
        });
        footRow += `<td class="text-center bg-dark-green text-white">
            <div>${grandTotalQty}</div>
            <div class="small">${formatCurrency(grandTotalVal)}</div>
        </td></tr>`;
        tfoot.append(footRow);

        const isBalance = lastAction === 'generate_inventory_report' && $('#inv_report_type').val() === 'inventory_balance';
        renderReportSidebar(summary, isBalance);
    }

    function renderReportTabs(parts, response) {
        const area = $('#reportSummaryTabsArea');
        area.empty().removeClass('d-none');

        const nav = $('<ul class="nav nav-tabs mb-3" role="tablist"></ul>');
        nav.append(`<li class="nav-item" role="presentation"><button class="nav-link active fw-bold text-uppercase" data-bs-toggle="tab" data-bs-target="#matrix-view" type="button">All Branches Matrix</button></li>`);

        Object.keys(parts).forEach(part_no => {
            nav.append(`<li class="nav-item" role="presentation"><button class="nav-link fw-bold text-uppercase" data-bs-toggle="tab" data-bs-target="#tab-${part_no.replace(/\s+/g, '-')}" type="button">${escapeHtml(part_no)}</button></li>`);
        });
        area.append(nav);

        const content = $('<div class="tab-content"></div>');
        content.append(`<div class="tab-pane fade show active" id="matrix-view" role="tabpanel"><div class="table-responsive"><table class="table table-bordered align-middle"><thead id="inventoryPreviewHead"></thead><tbody id="inventoryPreviewTableBody"></tbody><tfoot id="inventoryPreviewFoot"></tfoot></table></div></div>`);

        Object.keys(parts).forEach(part_no => {
            const partData = response.data.filter(d => d.part_no === part_no);
            let tabHtml = `<div class="tab-pane fade" id="tab-${part_no.replace(/\s+/g, '-')}" role="tabpanel">
                <div class="card border-dark-green shadow-sm">
                    <div class="card-header bg-dark-green text-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-white">PART NO: ${escapeHtml(part_no)} - ${escapeHtml(parts[part_no])}</h6>
                        <span class="badge bg-white text-dark-green fw-bold">${partData.length} Records</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-0 align-middle">
                                <thead class="bg-dark-green-light text-dark-green">
                                    <tr>
                                        <th>Branch</th>
                                        <th>Brand</th>
                                        <th>Category</th>
                                        <th class="text-center">Available</th>
                                        <th class="text-end">Cost</th>
                                        <th class="text-end">Total Value</th>
                                    </tr>
                                </thead>
                                <tbody>`;

            let totalQty = 0;
            let totalVal = 0;

            partData.forEach(row => {
                tabHtml += `<tr>
                    <td><span class="badge bg-dark-green">${escapeHtml(row.branch)}</span></td>
                    <td>${escapeHtml(row.brand)}</td>
                    <td>${escapeHtml(row.category || '-')}</td>
                    <td class="text-center fw-bold">${row.available}</td>
                    <td class="text-end">${formatCurrency(row.cost)}</td>
                    <td class="text-end fw-bold">${formatCurrency(row.total_value)}</td>
                </tr>`;
                totalQty += Number(row.available);
                totalVal += Number(row.total_value);
            });

            tabHtml += `</tbody>
                                <tfoot class="bg-dark-green-light fw-bold text-dark-green">
                                    <tr>
                                        <td colspan="3" class="text-end">TOTAL</td>
                                        <td class="text-center">${totalQty}</td>
                                        <td></td>
                                        <td class="text-end">${formatCurrency(totalVal)}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>`;
            content.append(tabHtml);
        });
        area.append(content);
    }

    function renderBrandDetailReport(brand, fullResponse) {
        const { data } = fullResponse;
        const brandData = data.filter(item => item.brand === brand);

        const thead = $('#inventoryPreviewHead');
        const tbody = $('#inventoryPreviewTableBody');
        const tfoot = $('#inventoryPreviewFoot');
        thead.empty(); tbody.empty(); tfoot.empty();

        thead.append(`<tr class="bg-dark-green text-white">
            <th>Branch</th>
            <th>Part No</th>
            <th>Description</th>
            <th>Category</th>
            <th class="text-center">Available</th>
            <th class="text-end">Cost</th>
            <th class="text-end">Total Value</th>
        </tr>`);

        let totalQty = 0;
        let totalVal = 0;

        brandData.forEach(row => {
            tbody.append(`<tr>
                <td><span class="badge bg-dark-green">${escapeHtml(row.branch)}</span></td>
                <td>${row.part_no}</td>
                <td>${row.description}</td>
                <td>${row.category || '-'}</td>
                <td class="text-center fw-bold">${row.available}</td>
                <td class="text-end">${formatCurrency(row.cost)}</td>
                <td class="text-end fw-bold">${formatCurrency(row.total_value)}</td>
            </tr>`);
            totalQty += Number(row.available);
            totalVal += Number(row.total_value);
        });

        tfoot.append(`<tr class="bg-dark-green-light fw-bold text-dark-green">
            <td colspan="4" class="text-end">GRAND TOTAL</td>
            <td class="text-center">${totalQty}</td>
            <td></td>
            <td class="text-end">${formatCurrency(totalVal)}</td>
        </tr>`);
    }

    function renderReportSidebar(s, isDetailed = false) {
        const sidebar = $('#reportSummarySidebar');
        sidebar.empty();
        if (!s) return;

        if (!isDetailed) {
            sidebar.html('');
            return;
        }

        html = `
            <div class="card border-dark-green mb-3 bg-white shadow-sm">
                <div class="card-body p-3 text-center">
                    <h6 class="text-uppercase fw-bold small text-dark-green mb-3">Beginning Balance</h6>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small">Balance Forward</span>
                        <div class="text-end">
                            <h4 class="fw-bold mb-0 text-dark-green">${s.total_beg || 0}</h4>
                            ${s.total_beg_value ? `<div class="small fw-bold mt-1 text-dark-green">₱ ${formatCurrency(s.total_beg_value)}</div>` : ''}
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-dark-green mb-3 bg-white shadow-sm">
                <div class="card-body p-3 text-center">
                    <h6 class="text-uppercase fw-bold small text-dark-green mb-3">Inventory In</h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Received Transfers</span>
                        <div class="text-end">
                            <span class="fw-bold text-dark-green">${s.total_received || 0}</span>
                            ${s.total_received_value ? `<div class="small text-muted">₱ ${formatCurrency(s.total_received_value)}</div>` : ''}
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">New Deliveries</span>
                        <div class="text-end">
                            <span class="fw-bold text-dark-green">${s.total_new || 0}</span>
                            ${s.total_new_value ? `<div class="small text-muted">₱ ${formatCurrency(s.total_new_value)}</div>` : ''}
                        </div>
                    </div>
                    <div class="d-flex justify-content-between border-top border-dark-green pt-2">
                        <span class="fw-bold text-dark-green small">TOTAL IN</span>
                        <div class="text-end">
                            <h4 class="fw-bold text-dark-green mb-0">${s.total_in || 0}</h4>
                            ${s.total_in_value ? `<div class="small fw-bold mt-1 text-dark-green">₱ ${formatCurrency(s.total_in_value)}</div>` : ''}
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-dark-green mb-3 bg-white shadow-sm">
                <div class="card-body p-3 text-center">
                    <h6 class="text-uppercase fw-bold small text-dark-green mb-3">Inventory Out</h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Transfers Out</span>
                        <div class="text-end">
                            <span class="fw-bold text-dark-green">${s.total_transfers_out || 0}</span>
                            ${s.total_transfers_out_value ? `<div class="small text-muted">₱ ${formatCurrency(s.total_transfers_out_value)}</div>` : ''}
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Sold Units</span>
                        <div class="text-end">
                            <span class="fw-bold text-dark-green">${s.total_sold || 0}</span>
                            ${s.total_sold_value ? `<div class="small text-muted">₱ ${formatCurrency(s.total_sold_value)}</div>` : ''}
                        </div>
                    </div>
                    <div class="d-flex justify-content-between border-top border-dark-green pt-2">
                        <span class="fw-bold text-dark-green small">TOTAL OUT</span>
                        <div class="text-end">
                            <h4 class="fw-bold text-dark-green mb-0">${s.total_out || 0}</h4>
                            ${s.total_out_value ? `<div class="small fw-bold mt-1 text-dark-green">₱ ${formatCurrency(s.total_out_value)}</div>` : ''}
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-dark-green bg-white shadow-sm">
                <div class="card-body p-3 text-center">
                    <h6 class="text-uppercase fw-bold small text-dark-green mb-3">Ending Balance</h6>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Actual</span>
                        <div class="text-end">
                            <h4 class="fw-bold text-dark-green mb-0">${s.total_end || s.total_available || 0}</h4>
                            ${s.total_value ? `<div class="small fw-bold mt-1 text-dark-green">₱ ${formatCurrency(s.total_value)}</div>` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
        sidebar.html(html);

    }

    function renderBrandSummary(summary) {
        const area = $('#reportBrandSummaryArea');
        area.empty();

        const partSummary = summary.part_summary || summary.brand_summary;
        if (!partSummary || partSummary.length === 0) return;

        let html = `
            <div class="card border-dark-green shadow-sm overflow-hidden bg-white">
                <div class="card-header bg-dark-green text-white text-center py-2">
                    <h6 class="mb-0 fw-bold small text-uppercase text-white">Summary of Quantity Per Part</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0 align-middle">
                        <thead class="bg-dark-green-light text-dark-green">
                            <tr>
                                <th class="ps-3">Part No / Description</th>
                                <th class="text-center" style="width: 150px;">Quantity</th>
                            </tr>
                        </thead>
                        <tbody>
        `;

        partSummary.forEach(item => {
            html += `
                <tr>
                    <td class="ps-3 small"><strong>${escapeHtml(item.part_no || '')}</strong> ${escapeHtml(item.description || '')}</td>
                    <td class="text-center fw-bold text-dark-green">${item.quantity}</td>
                </tr>
            `;
        });

        const total = partSummary.reduce((acc, curr) => acc + Number(curr.quantity), 0);
        html += `
                <tr class="bg-dark-green-light fw-bold text-uppercase text-dark-green">
                    <td class="ps-3">TOTAL</td>
                    <td class="text-center">${total}</td>
                </tr>
            </tbody>
        </table>
        </div>
        </div>
        `;
        $('#reportBrandSummaryArea').html(html);
    }

    function renderInventoryHistory(data) {
        const tbody = $('#historyTableBody');
        tbody.empty();

        if (!data || data.length === 0) {
            tbody.html('<tr><td colspan="7" class="text-center text-muted py-4">No history records found for this item.</td></tr>');
            return;
        }

        data.forEach(h => {
            // Only show completed statuses. 'In-Transit' is hidden in Movement History.
            if (h.status === 'In-Transit') {
                return;
            }

            let eventText = '';
            let fromText = escapeHtml(h.from_location || '-');
            let toText = escapeHtml(h.to_location || '-');

            switch (h.type) {
                case 'IN':
                    eventText = 'Delivered';
                    fromText = 'Supplier';
                    toText = escapeHtml(h.to_location || h.from_location || '-');
                    break;
                case 'OUT':
                    eventText = 'Sold';
                    toText = escapeHtml(h.customer_name || 'Customer');
                    break;
                case 'TRANSFER_IN':
                case 'TRANSFER_OUT':
                    eventText = 'Transferred';
                    break;
                case 'ADJUSTMENT':
                    eventText = 'Adjustment';
                    break;
                default:
                    eventText = h.type;
            }

            tbody.append(`
                <tr class="align-middle">
                    <td>${h.transaction_date}</td>
                    <td>${eventText}</td>
                    <td class="text-center fw-bold">${h.quantity}</td>
                    <td>${fromText}</td>
                    <td>${toText}</td>
                    <td><span class="text-muted">${escapeHtml(h.status || 'Completed')}</span></td>
                    <td>${escapeHtml(h.or_number || '-')}</td>
                </tr>
            `);
        });
    }

    function exportToCSV(data, title) {
        if (!data.length) return;
        const config = getReportConfig(lastAction, data);
        const csv = [
            config.headers.join(','),
            ...data.map(row => config.keys.map(key => `"${row[key] || ''}"`).join(','))
        ].join('\n');

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.setAttribute('download', `${title.replace(/\s+/g, '_')}_${new Date().toISOString().split('T')[0]}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function exportToExcel(data, title) {
        if (!data.length) return;
        const config = getReportConfig(lastAction, data);

        // Calculate totals
        let totalQty = 0, totalAmount = 0;
        data.forEach(row => {
            if (row.quantity) totalQty += Number(row.quantity);
            if (row.total_amount) totalAmount += Number(row.total_amount);
            else if (row.amount) totalAmount += Number(row.amount);
        });

        const worksheetData = [
            config.headers,
            ...data.map(row => config.keys.map(key => row[key]))
        ];

        // Add Grand Total row if applicable
        if (config.showFooter) {
            const footerRow = new Array(config.headers.length).fill('');
            const qtyIndex = config.headers.indexOf('Qty');
            const totalIndex = config.headers.length - 1;

            footerRow[config.headers.indexOf(config.headers.find(h => h.includes('Date') || h.includes('Branch') || h === 'Total' || h === 'Amount')) - 1 || 0] = 'GRAND TOTAL';
            if (qtyIndex !== -1) footerRow[qtyIndex] = totalQty;
            footerRow[totalIndex] = totalAmount;
            worksheetData.push(footerRow);
        }

        // Add Signatures
        worksheetData.push([]);
        worksheetData.push([]);
        worksheetData.push(['Prepared By:', '', '', 'Noted By:']);
        worksheetData.push(['________________', '', '', '________________']);

        const worksheet = XLSX.utils.aoa_to_sheet(worksheetData);
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, "Report");
        XLSX.writeFile(workbook, `${title.replace(/\s+/g, '_')}_${new Date().toISOString().split('T')[0]}.xlsx`);
    }

    function exportToPDF(data, title) {
        if (!data.length) return;
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('l', 'mm', 'a4');
        const config = getReportConfig(lastAction, data);

        // BLACK & WHITE HEADER
        doc.setFontSize(22);
        doc.setTextColor(0, 0, 0); // Black
        doc.text('Spare Parts Inventory', 14, 20);

        doc.setFontSize(10);
        doc.setTextColor(100);
        doc.text(`REPORT: ${title.toUpperCase()}`, 14, 28);
        doc.text(`Generated on: ${new Date().toLocaleString()}`, 14, 33);
        doc.setDrawColor(0);
        doc.line(14, 36, 283, 36);

        // Calculate totals for footer
        let totalQty = 0, totalAmount = 0;
        data.forEach(row => {
            if (row.quantity) totalQty += Number(row.quantity);
            if (row.total_amount) totalAmount += Number(row.total_amount);
            else if (row.amount) totalAmount += Number(row.amount);
        });

        const body = data.map(row => config.keys.map(key => {
            let val = row[key] || '-';
            if (config.formatters[key] && key !== 'display_branch') val = config.formatters[key](val);
            return val;
        }));

        // Prepare footer for the table
        let foot = [];
        if (config.showFooter) {
            const footerRow = new Array(config.headers.length).fill('');
            const qtyIndex = config.headers.indexOf('Qty');
            footerRow[0] = 'GRAND TOTAL';
            if (qtyIndex !== -1) footerRow[qtyIndex] = totalQty.toString();
            footerRow[config.headers.length - 1] = formatCurrency(totalAmount);
            foot = [footerRow];
        }

        doc.autoTable({
            head: [config.headers],
            body: body,
            foot: foot,
            startY: 42,
            theme: 'grid',
            styles: { fontSize: 8, cellPadding: 3, valign: 'middle', textColor: 0 },
            headStyles: { fillColor: [240, 240, 240], textColor: 0, fontStyle: 'bold', lineWidth: 0.1, lineColor: 0 },
            footStyles: { fillColor: [255, 255, 255], textColor: 0, fontStyle: 'bold', lineWidth: 0.1, lineColor: 0 },
            alternateRowStyles: { fillColor: [255, 255, 255] },
            margin: { top: 42, bottom: 40 },
            didDrawPage: function (d) {
                doc.setFontSize(8);
                doc.setTextColor(150);
                doc.text(`Page ${d.pageNumber}`, 270, 200);

                // Add signatures at the bottom of the last page or every page?
                // Usually bottom of the report. Let's do it after the table.
            }
        });

        // Add Signatures after the table
        const finalY = doc.lastAutoTable.finalY + 20;
        if (finalY < 180) { // Check if there's space on the current page
            doc.setFontSize(10);
            doc.setTextColor(0);
            doc.text('Prepared By:', 14, finalY);
            doc.text('__________________________', 14, finalY + 10);

            doc.text('Noted By:', 150, finalY);
            doc.text('__________________________', 150, finalY + 10);
        } else {
            doc.addPage();
            doc.text('Prepared By:', 14, 20);
            doc.text('__________________________', 14, 30);

            doc.text('Noted By:', 150, 20);
            doc.text('__________________________', 150, 30);
        }

        doc.save(`${title.replace(/\s+/g, '_')}_${new Date().toISOString().split('T')[0]}.pdf`);
    }

    function printReport(data, title) {
        const invType = $('#inv_report_type').val();
        const isMatrix = lastAction === 'generate_inventory_report' && invType === 'inventory_summary';

        const config = getReportConfig(lastAction, data);
        const printWindow = window.open('', '_blank');
        const s = currentReportSummary;

        let tableHtml = '';

        if (isMatrix && currentMatrixData) {
            // RENDER MATRIX TABLE FOR PRINT
            const matrix = currentMatrixData;
            const branches = currentBranches;
            const parts = currentParts;

            tableHtml = `<table class="table table-bordered table-sm mt-4 text-center">
                <thead>
                    <tr class="bg-dark-green text-white">
                        <th class="text-start">PART NO / DESCRIPTION</th>
                        ${branches.map(b => `<th>${b}</th>`).join('')}
                        <th>TOTAL</th>
                    </tr>
                </thead>
                <tbody>`;

            const matrixMap = {};
            matrix.forEach(item => {
                if (!matrixMap[item.part_no]) matrixMap[item.part_no] = {};
                matrixMap[item.part_no][item.branch] = Number(item.total_qty);
            });

            let grandTotal = 0;
            const bTotals = {};

            Object.keys(parts).forEach(part_no => {
                const description = parts[part_no];
                tableHtml += `<tr><td class="text-start fw-bold small"><strong>${part_no}</strong><br><small>${description}</small></td>`;
                let partTotal = 0;
                branches.forEach(branch => {
                    const qty = matrixMap[part_no]?.[branch] || 0;
                    tableHtml += `<td>${qty > 0 ? qty : '-'}</td>`;
                    partTotal += qty;
                    bTotals[branch] = (bTotals[branch] || 0) + qty;
                });
                tableHtml += `<td class="fw-bold">${partTotal}</td></tr>`;
                grandTotal += partTotal;
            });

            tableHtml += `<tr class="bg-dark-green-light fw-bold text-dark-green">
                <td class="text-start">GRAND TOTAL</td>
                ${branches.map(b => `<td>${bTotals[b] || 0}</td>`).join('')}
                <td>${grandTotal}</td>
            </tr></tbody></table>`;
        } else {
            // REGULAR TABLE FOR PRINT
            tableHtml = `<table class="table table-bordered table-sm mt-4">
                <thead class="bg-dark-green text-white"><tr>${config.headers.map(h => `<th class="text-nowrap">${h}</th>`).join('')}</tr></thead>
                <tbody>`;

            data.forEach(row => {
                tableHtml += '<tr>';
                config.keys.forEach(key => {
                    let val = row[key] !== undefined && row[key] !== null ? row[key] : '-';
                    if (config.formatters[key]) val = config.formatters[key](val);
                    tableHtml += `<td>${val}</td>`;
                });
                tableHtml += '</tr>';
            });
            tableHtml += '</tbody></table>';
        }

        let summaryHtml = '';
        if (s) {
            const isBalance = lastAction === 'generate_inventory_report' && invType === 'inventory_balance';

            if (isBalance) {
                // DETAILED SUMMARY FOR BALANCE REPORT
                summaryHtml = `
                    <div class="row mt-5 g-4">
                        <div class="col-3">
                            <div class="border p-3 text-center border-dark-green bg-white">
                                <h6 class="small text-dark-green text-uppercase fw-bold mb-3">Beginning Balance</h6>
                                <h4 class="fw-bold mb-1 text-dark-green">${s.total_beg || 0}</h4>
                                ${s.total_beg_value ? `<div class="small fw-bold mt-1 text-dark-green">₱ ${formatCurrency(s.total_beg_value)}</div>` : ''}
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="border p-3 text-center border-dark-green bg-white">
                                <h6 class="small text-dark-green text-uppercase fw-bold mb-3">Inventory In</h6>
                                <h4 class="fw-bold mb-1 text-dark-green">${s.total_in || 0}</h4>
                                <div class="small text-muted mt-1">Rec: ${s.total_received || 0} | New: ${s.total_new || 0}</div>
                                ${s.total_in_value ? `<div class="small fw-bold mt-2 text-dark-green">₱ ${formatCurrency(s.total_in_value)}</div>` : ''}
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="border p-3 text-center border-dark-green bg-white">
                                <h6 class="small text-dark-green text-uppercase fw-bold mb-3">Inventory Out</h6>
                                <h4 class="fw-bold mb-1 text-dark-green">${s.total_out || 0}</h4>
                                <div class="small text-muted mt-1">Sold: ${s.total_sold || 0} | Trans: ${s.total_transfers_out || 0}</div>
                                ${s.total_out_value ? `<div class="small fw-bold mt-2 text-dark-green">₱ ${formatCurrency(s.total_out_value)}</div>` : ''}
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="border p-3 text-center border-dark-green bg-white" style="border-width: 2px !important;">
                                <h6 class="small text-dark-green text-uppercase fw-bold mb-3">Ending Balance</h6>
                                <h4 class="fw-bold mb-1 text-dark-green">${s.total_end || s.total_available || 0}</h4>
                                 ${s.total_value ? `<div class="small fw-bold mt-2 text-dark-green">₱ ${formatCurrency(s.total_value)}</div>` : ''}
                            </div>
                        </div>
                    </div>`;
            } else {
                // SIMPLE SUMMARY FOR OTHER REPORTS
                // Hide for inventory_summary
                if (invType === 'inventory_summary') {
                    summaryHtml = '';
                } else {
                    // Hide for everything except inventory_balance as requested
                    summaryHtml = '';
                }
            }

            // Append Part Summary (Tally Board)
            const partSummary = s.part_summary || s.brand_summary || [];
            if (partSummary.length > 0) {
                summaryHtml += `
                <div class="row mt-5 justify-content-center">
                    <div class="col-8">
                        <div class="card border-dark-green bg-white shadow-sm">
                            <div class="card-header bg-dark-green text-white text-center py-2">
                                <h6 class="mb-0 fw-bold small text-uppercase">Summary of Quantity Per Part</h6>
                            </div>
                            <table class="table table-bordered table-sm mb-0">
                                <thead class="bg-dark-green-light">
                                    <tr><th class="ps-3 text-dark-green">Part No / Description</th><th class="text-center text-dark-green">Quantity</th></tr>
                                </thead>
                                <tbody>
                                    ${partSummary.map(p => `<tr>
                                        <td class="ps-3 small"><strong>${p.part_no || ''}</strong> ${p.description || p.brand || ''}</td>
                                        <td class="text-center fw-bold text-dark-green">${p.quantity}</td>
                                    </tr>`).join('')}
                                    <tr class="bg-dark-green-light fw-bold text-uppercase text-dark-green">
                                        <td class="ps-3">TOTAL</td>
                                        <td class="text-center">${partSummary.reduce((a, c) => a + Number(c.quantity), 0)}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>`;
            }
        }

        printWindow.document.write(`
            <html>
                <head>
                    <title>${title}</title>
                    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'>
                    <style>
                    body { font-family: Calibri, sans-serif; padding: 20px; color: #333; }
                    .report-header { text-align: center; border-bottom: 2px solid #1a4d2e; padding-bottom: 15px; margin-bottom: 25px; }
                    .report-header h2 { margin: 0; color: #1a4d2e; text-transform: uppercase; }
                    .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 11px; }
                    .table th, .table td { border: 1px solid #dee2e6; padding: 6px; }
                    .bg-dark-green { background-color: #1a4d2e !important; color: white !important; font-weight: bold; }
                    .text-dark-green { color: #1a4d2e !important; }
                    .bg-light { background-color: #f8f9fa !important; }
                    .bg-dark-green-light { background-color: rgba(26, 77, 46, 0.05) !important; }
                    .text-center { text-align: center; }
                    .text-start { text-align: left; }
                    .text-end { text-align: right; }
                    .fw-bold { font-weight: bold; }
                    .mt-4 { margin-top: 1.5rem; }
                    .mt-5 { margin-top: 3rem; }
                    .row { display: flex; flex-wrap: wrap; margin: 0 -15px; }
                    .col-3 { flex: 0 0 25%; max-width: 25%; padding: 0 15px; }
                    .col-4 { flex: 0 0 33.33%; max-width: 33.33%; padding: 0 15px; }
                    .col-8 { flex: 0 0 66.66%; max-width: 66.66%; padding: 0 15px; }
                    .offset-md-8 { margin-left: 66.66%; }
                    .border { border: 1px solid #dee2e6 !important; }
                    .border-dark-green { border: 1px solid #1a4d2e !important; }
                    .p-3 { padding: 1rem !important; }
                    .mb-0 { margin-bottom: 0 !important; }
                    .mb-1 { margin-bottom: 0.25rem !important; }
                    .mb-2 { margin-bottom: 0.5rem !important; }
                    .mb-3 { margin-bottom: 1rem !important; }
                    .d-flex { display: flex !important; }
                    .justify-content-between { justify-content: space-between !important; }
                    .justify-content-center { justify-content: center !important; }
                    .align-items-center { align-items: center !important; }
                    .border-top { border-top: 1px solid #dee2e6 !important; }
                    .card { border-radius: 0; border: 1px solid #dee2e6; }
                    .card-header { padding: 8px 15px; border-bottom: 1px solid #dee2e6; }
                </style>
                </head>
                <body>
                    <div class="report-header">
                        <h4>SOLID MOTORCYCLE DISTRIBUTORS, INC.</h4>
                        <h5>${title}</h5>
                        <div class="small fw-bold text-muted mb-1">Generated on: ${new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'numeric', day: 'numeric' })}</div>
                        <div class="filters">FILTERS: ALL</div>
                    </div>
                    
                    ${tableHtml}
                    ${summaryHtml}

                    <div class="footer-signatures row">
                        <div class="col-4 text-center">
                            <div class="sig-line"></div>
                            <div class="mt-2 fw-bold text-uppercase">Prepared By</div>
                        </div>
                        <div class="col-4 text-center">
                            <div class="sig-line"></div>
                            <div class="mt-2 fw-bold text-uppercase">Checked By</div>
                        </div>
                        <div class="col-4 text-center">
                            <div class="sig-line"></div>
                            <div class="mt-2 fw-bold text-uppercase">Approved By</div>
                        </div>
                    </div>
                    <script>
                        window.onload = function() { window.print(); window.close(); };
                    </script>
                </body>
            </html>
            `);
        printWindow.document.close();
    }

    // Placeholder functions to prevent errors during Phase 1
    function loadDashboardStats() {
        $('#dashboard-loader').removeClass('d-none');
        $('#dashboard-content').addClass('d-none');

        loadApiData('get_dashboard_stats', data => {
            $('#stat-total-qty').text(data.total_quantity || '0');
            $('#stat-inventory-value').text(formatCurrency(data.total_value || 0));
            $('#stat-monthly-sales').text(formatCurrency(data.monthly_sales || 0));
            $('#stat-yearly-sales').text(formatCurrency(data.yearly_sales || 0));
            $('#stat-outstanding-balance').text(formatCurrency(data.outstanding_balance || 0));
            $('#stat-total-accounts').text(data.total_accounts || '0');

            $('#dashboard-loader').addClass('d-none');
            $('#dashboard-content').removeClass('d-none');
        });
    }

    function loadInventory() {
        loadApiData('get_inventory_list', data => {
            inventoryData = data;
            renderInventory(data);
        }, 'inventoryTableBody');
    }

    function renderInventory(data) {
        const tbody = $('#inventoryTableBody');
        tbody.empty();

        if (!data || data.length === 0) {
            tbody.html('<tr><td colspan="10" class="text-center text-muted py-4">No inventory items found.</td></tr>');
            return;
        }

        data.forEach(item => {
            const stockLevel = Number(item.current_stock);
            const minStock = Number(item.min_stock || 0);
            const stockClass = stockLevel <= minStock ? 'text-danger fw-bold' : '';
            const statusBadge = stockLevel <= minStock ? '<span class="badge bg-danger">Low Stock</span>' : '<span class="badge bg-success">In Stock</span>';
            const totalValue = stockLevel * Number(item.cost);

            tbody.append(`
            <tr class="align-middle">
                ${window.isBranchPage ? `
                <td class="text-center">
                    <input type="checkbox" class="form-check-input inventory-checkbox" data-id="${item.id}">
                </td>` : ''}
                <td><span class="badge bg-light text-dark border">${escapeHtml(item.brand)}</span></td>
                <td><div class="fw-bold">${escapeHtml(item.part_no)}</div></td>
                <td>${escapeHtml(item.description)}</td>
                ${!isBranchPage ? `<td class="text-center"><span class="badge bg-secondary">${escapeHtml(item.current_branch)}</span></td>` : ''}
                <td class="text-center ${stockClass}">${stockLevel}</td>
                <td class="text-end ">${formatCurrency(item.cost)}</td>
                <td class="text-end fw-bold text-success ">${formatCurrency(item.price)}</td>
                <td class="text-end ">${formatCurrency(totalValue)}</td>
                <td class="text-center">${statusBadge}</td>
                <td class="text-center">
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-primary edit-part-btn" data-id="${item.id}" title="Edit Part">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-info view-history-btn" data-id="${item.id}" title="View History">
                            <i class="bi bi-clock-history"></i>
                        </button>
                        ${canDelete ? `
                        <button class="btn btn-sm btn-outline-danger delete-part-btn" data-id="${item.id}" title="Delete Part">
                            <i class="bi bi-trash"></i>
                        </button>` : ''}
                    </div>
                </td>
            </tr>
            `);
        });
        updateInventoryBatchButtons();
    }

    function loadSales() {
        loadApiData('get_sales_list', data => {
            salesData = data;
            renderSales(data);
        }, 'salesTableBody');
    }

    function renderSales(data, typeFilter = 'all') {
        salesData = data;
        const tbody = $('#salesTableBody');
        tbody.empty();

        let filtered = data;
        if (typeFilter !== 'all') {
            filtered = data.filter(s => s.transaction_type && s.transaction_type.toLowerCase() === typeFilter.toLowerCase());
        }

        if (!filtered || filtered.length === 0) {
            tbody.html('<tr><td colspan="8" class="text-center text-muted py-4">No sales records found.</td></tr>');
            return;
        }

        filtered.forEach(s => {
            const typeBadge = s.transaction_type === 'charge' ? 'bg-info text-dark' : 'bg-success text-white';
            const balanceVal = Number(s.balance || 0);
            const balanceText = s.transaction_type === 'charge' ? formatCurrency(balanceVal) : '-';

            tbody.append(`
            <tr class="align-middle">
                ${window.isBranchPage ? `
                <td class="text-center">
                    <input type="checkbox" class="form-check-input sales-checkbox" data-id="${s.id}" data-or="${s.or_number}" data-branch="${s.from_location}">
                </td>` : ''}
                <td><div class="fw-bold">${s.sale_date}</div></td>
                ${!isBranchPage ? `<td><span class="badge bg-light text-dark border">${escapeHtml(s.from_location)}</span></td>` : ''}
                <td><div class="fw-bold">${escapeHtml(s.customer_name)}</div></td>
                <td><div class="small text-muted ">${escapeHtml(s.or_number)}</div></td>
                <td class="text-end fw-bold">${formatCurrency(s.total_amount)}</td>
                <td class="text-center"><span class="badge ${typeBadge}">${escapeHtml(s.transaction_type.toUpperCase())}</span></td>
                <td class="text-end  ${balanceVal > 0 ? 'text-danger' : ''}">${balanceText}</td>
                <td class="text-center">
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-primary view-sale-btn" data-id="${s.id}" data-or="${s.or_number}" data-branch="${s.from_location}" title="View Sale">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-warning edit-sale-btn" data-id="${s.id}" data-or="${s.or_number}" data-branch="${s.from_location}" title="Edit Sale">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        ${canDelete ? `
                        <button class="btn btn-sm btn-outline-danger delete-sale-btn" data-id="${s.or_number}" data-branch="${s.from_location}" title="Delete Sale">
                            <i class="bi bi-trash"></i>
                        </button>` : ''}
                    </div>
                </td>
            </tr>
            `);
        });
        updateSalesBatchButtons();
    }

    function loadPayments() {
        loadApiData('get_payments_list', data => {
            paymentsData = data;
            renderPayments(data);
        }, 'paymentsTableBody');
    }

    function renderPayments(data) {
        const tbody = $('#paymentsTableBody');
        tbody.empty();

        if (!data || data.length === 0) {
            tbody.html('<tr><td colspan="6" class="text-center text-muted py-4">No payment records found.</td></tr>');
            return;
        }

        data.forEach(p => {
            tbody.append(`
            <tr class="align-middle">
                <td><div class="fw-bold">${p.transaction_date}</div></td>
                ${!isBranchPage ? `<td><span class="badge bg-secondary">${escapeHtml(p.from_location)}</span></td>` : ''}
                <td><div class="fw-bold">${escapeHtml(p.customer_name)}</div></td>
                <td class="text-end fw-bold text-success">₱${formatCurrency(p.amount)}</td>
                <td class="text-center"><span class="badge bg-light text-dark border ">${escapeHtml(p.or_number)}</span></td>
                <td class="text-center">
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-warning edit-payment-btn" data-id="${p.id}" title="Edit Payment">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-primary view-receipt-btn" data-id="${p.id}" title="Print Receipt">
                            <i class="bi bi-printer"></i>
                        </button>
                        ${canDelete ? `
                        <button class="btn btn-sm btn-outline-danger delete-payment-btn" data-id="${p.id}" data-branch="${p.from_location}" title="Delete Payment">
                            <i class="bi bi-trash"></i>
                        </button>` : ''}
                    </div>
                </td>
            </tr>
            `);
        });
    }

    function loadAging() {
        const branch = $('#agingBranchFilter').val();
        const search = $('#paymentsAgingSearch').val();
        loadApiData('get_aging_summary', data => {
            paymentsAgingData = data; // Store globally for autocomplete search
            renderAging(data);
        }, 'paymentsAgingTableBody', { branch: branch, search: search });
    }

    function renderAging(data) {
        const tbody = $('#paymentsAgingTableBody');
        tbody.empty();

        if (!data || data.length === 0) {
            tbody.html('<tr><td colspan="6" class="text-center text-muted py-4">No active accounts found.</td></tr>');
            return;
        }

        data.forEach((row, index) => {
            tbody.append(`
            <tr class="align-middle aging-row" data-customer="${escapeHtml(row.customer_name)}" data-branch="${escapeHtml(row.branch)}">
                    <td class="text-center"><i class="bi bi-chevron-right text-muted expand-icon"></i></td>
                    ${!isBranchPage ? `<td><span class="badge bg-secondary">${escapeHtml(row.branch)}</span></td>` : ''}
                    <td><div class="fw-bold fs-6">${escapeHtml(row.customer_name)}</div></td>
                    <td class="text-end ">₱${formatCurrency(row.age_0_30 || 0)}</td>
                    <td class="text-end ">₱${formatCurrency(row.age_31_60 || 0)}</td>
                    <td class="text-end ">₱${formatCurrency(row.age_61_90 || 0)}</td>
                    <td class="text-end  text-danger">₱${formatCurrency(row.age_over_90 || 0)}</td>
                    <td class="text-end fw-bold fs-5 text-danger ">₱${formatCurrency(row.total_balance)}</td>
                    <td class="text-center">
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-dark print-ledger-btn" data-customer="${escapeHtml(row.customer_name)}" data-branch="${escapeHtml(row.branch)}" title="Print Statement">
                                <i class="bi bi-printer"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success record-payment-btn " data-customer="${escapeHtml(row.customer_name)}" data-branch="${escapeHtml(row.branch)}" title="Record Payment">
                                <i class="bi bi-cash-coin"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            <tr class="ledger-details-row d-none" id="ledger-${index}">
                <td colspan="9" class="p-0 border-0 bg-light">
                    <div class="p-3">
                        <h6 class="fw-bold mb-3 small text-muted text-uppercase"><i class="bi bi-journal-text me-2"></i>Transaction Ledger</h6>
                        <div class="table-responsive rounded shadow-sm bg-white">
                            <table class="table table-sm table-bordered mb-0 small">
                                <thead class="bg-light">
                                    <tr class="text-muted">
                                        <th>DATE</th>
                                        <th>OR # / REF</th>
                                        <th class="text-end">DEBIT (SALES)</th>
                                        <th class="text-end">CREDIT (PAYMENTS)</th>
                                        <th class="text-end">BALANCE</th>
                                    </tr>
                                </thead>
                                <tbody class="ledger-content">
                                    <tr><td colspan="5" class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </td>
            </tr>
        `);
        });

        // Toggle Expand Logic
        $('.aging-row').on('click', function (e) {
            if ($(e.target).closest('button').length) return; // Don't expand if clicking the button

            const row = $(this);
            const detailRow = row.next('.ledger-details-row');
            const icon = row.find('.expand-icon');
            const customer = row.data('customer');
            const branch = row.data('branch');

            if (detailRow.hasClass('d-none')) {
                // Expanding
                $('.ledger-details-row').addClass('d-none');
                $('.expand-icon').removeClass('bi-chevron-down').addClass('bi-chevron-right');

                detailRow.removeClass('d-none');
                icon.removeClass('bi-chevron-right').addClass('bi-chevron-down');

                loadCustomerLedger(customer, branch, detailRow.find('.ledger-content'));
            } else {
                // Collapsing
                detailRow.addClass('d-none');
                icon.removeClass('bi-chevron-down').addClass('bi-chevron-right');
            }
        });
    }

    function loadCustomerLedger(customer, branch, container) {
        $.get('../api/spareparts_inventory.php?action=get_customer_ledger', { customer: customer, branch: branch }, response => {
            if (response.success) {
                let html = '';
                if (response.data.length === 0) {
                    html = '<tr><td colspan="5" class="text-center text-muted py-3">No transaction history.</td></tr>';
                } else {
                    response.data.forEach(l => {
                        const balanceClass = l.balance > 0 ? 'text-danger fw-bold' : 'text-success fw-bold';
                        const typeIcon = l.type === 'OUT' ? '<i class="bi bi-cart me-2 text-primary"></i>' : '<i class="bi bi-cash-coin me-2 text-success"></i>';
                        html += `
                            <tr>
                                <td>${l.date}</td>
                                <td>${typeIcon}${l.or_number} <small class="text-muted">(${l.type})</small></td>
                                <td class="text-end">${l.sale_amount > 0 ? '₱' + formatCurrency(l.sale_amount) : '-'}</td>
                                <td class="text-end  text-success">${l.payment_amount > 0 ? '₱' + formatCurrency(l.payment_amount) : '-'}</td>
                                <td class="text-end  ${balanceClass}">₱${formatCurrency(l.balance)}</td>
                            </tr>
            `;
                    });
                }
                container.html(html);
            } else {
                container.html(`<tr><td colspan="5" class="text-center text-danger py-3">${response.message}</td></tr>`);
            }
        }, 'json');
    }

    function loadTransfers() {
        loadApiData('get_transfers_list', data => {
            transfersData = data;
            renderTransfers(data);
        }, 'transfersTableBody');
    }

    function renderTransfers(transfers) {
        let html = '';
        transfers.forEach(t => {
            let statusBadge = '';
            switch (t.status) {
                case 'Completed': statusBadge = '<span class="badge bg-dark-green text-white">Completed</span>'; break;
                case 'In-Transit': statusBadge = '<span class="badge bg-warning text-dark">In-Transit</span>'; break;
                case 'Rejected': statusBadge = '<span class="badge bg-danger text-white">Rejected</span>'; break;
                default: statusBadge = `<span class="badge bg-secondary text-white">${t.status}</span>`;
            }

            html += `
                <tr class="align-middle">
                    <td><div class="fw-bold">${t.transfer_date}</div></td>
                    <td class="text-center"><span class="badge bg-light text-dark border px-3">${t.item_count} Items</span></td>
                    <td><span class="badge bg-light text-dark-green border border-dark-green">${escapeHtml(t.from_branch)}</span></td>
                    <td><span class="badge bg-light text-dark-green border border-dark-green">${escapeHtml(t.to_branch)}</span></td>
                    <td class="text-center">${statusBadge}</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-dark-green view-transfer-btn" data-id="${t.id}" title="View Details">
                            <i class="bi bi-eye"></i>
                        </button>
                        ${canDelete && t.status === 'In-Transit' ? `
                        <button class="btn btn-sm btn-outline-danger cancel-transfer-btn" data-id="${t.id}" title="Cancel Transfer">
                            <i class="bi bi-x-circle"></i>
                        </button>` : ''}
                    </div>
                </td>
            </tr>
            `;
        });
        $('#transfersTableBody').html(html || '<tr><td colspan="6" class="text-center text-muted py-4">No outgoing transfers found.</td></tr>');

        // Add Listener
        $('.view-transfer-btn').off('click').on('click', function () {
            loadTransferDetails($(this).data('id'));
        });
    }

    function loadIncomingTransfers() {
        loadApiData('get_incoming_transfers', data => {
            renderIncomingTransfers(data);
        }, 'incomingTransfersTableBody');
    }

    function renderIncomingTransfers(transfers) {
        let html = '';
        transfers.forEach(t => {
            let statusBadge = '';
            switch (t.status) {
                case 'Completed': statusBadge = '<span class="badge bg-success text-white">Completed</span>'; break;
                case 'In-Transit': statusBadge = '<span class="badge bg-warning text-dark">In-Transit</span>'; break;
                case 'Rejected': statusBadge = '<span class="badge bg-danger text-white">Rejected</span>'; break;
                default: statusBadge = `<span class="badge bg-secondary text-white">${t.status}</span>`;
            }

            html += `
                <tr class="align-middle">
                    <td><div class="fw-bold">${t.transfer_date}</div></td>
                    <td class="text-center"><span class="badge bg-light text-dark border px-3">${t.item_count || 0} Items</span></td>
                    <td><span class="badge bg-light text-success border border-success">${escapeHtml(t.from_branch)}</span></td>
                    <td class="text-center">${statusBadge}</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-primary view-transfer-btn" data-id="${t.id}">View</button>
                    </td>
                </tr>
            `;
        });
        $('#incomingTransfersTableBody').html(html || '<tr><td colspan="5" class="text-center py-4 text-muted">No pending transfers found.</td></tr>');
    }

    function loadGlobalTransfers() {
        loadApiData('get_global_transfers', data => {
            globalTransfersData = data;
            renderGlobalTransfers(data);
        });
    }

    function loadActivityLog() {
        loadApiData('get_activity_log', data => {
            activityLogData = data;
            renderActivityLog(data);
        }, 'activityLogTableBody');
    }

    function renderGlobalTransfers(data) {
        ['in-transit', 'completed', 'rejected'].forEach(status => {
            $(`#col-${status}`).empty();
        });

        data.forEach(t => {
            const containerId = `col-${t.status.toLowerCase()}`;
            if (!$(`#${containerId}`).length) return;

            const borderColor = t.status === 'In-Transit' ? 'border-warning' : (t.status === 'Completed' ? 'border-dark-green' : 'border-danger');

            $(`#${containerId}`).append(`
            <div class="card mb-3 border-start border-4 ${borderColor} shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold text-dark-green">REF #${t.id}</span>
                        <span class="small text-muted"><i class="bi bi-calendar-event me-1"></i>${t.transfer_date}</span>
                    </div>
                    <div class="mb-2">
                        <div class="small fw-bold text-uppercase text-muted" style="font-size: 0.7rem;">Route</div>
                        <div class="d-flex align-items-center">
                            <span class="badge bg-light text-dark-green border border-dark-green">${escapeHtml(t.from_branch)}</span>
                            <i class="bi bi-arrow-right mx-2 text-muted"></i>
                            <span class="badge bg-light text-dark-green border border-dark-green">${escapeHtml(t.to_branch)}</span>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-end">
                        <div class="small fw-bold text-uppercase text-muted" style="font-size: 0.7rem;">Items : <span class="badge bg-light text-dark border">${t.item_count}</span></div>
                        <button class="btn btn-sm btn-link text-decoration-none p-0 text-dark-green fw-bold view-global-transfer-btn" data-id="${t.id}">
                            Details <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
            `);
        });

        $('#count-in-transit').text(data.filter(t => t.status === 'In-Transit').length);
        $('#count-completed').text(data.filter(t => t.status === 'Completed').length);
        $('#count-rejected').text(data.filter(t => t.status === 'Rejected').length);

        // Add Listener
        $('.view-global-transfer-btn').off('click').on('click', function () {
            loadTransferDetails($(this).data('id'));
        });
    }

    function loadTransferDetails(transactionId) {
        const modal = new bootstrap.Modal(document.getElementById('viewTransferDetailsModal'));
        const body = $('#transferDetailsBody');

        body.html('<div class="text-center p-5"><div class="spinner-border text-dark-green"></div><p class="mt-2">Loading details...</p></div>');
        modal.show();

        loadApiData('get_transfer_details&id=' + transactionId, data => {
            if (!data || data.length === 0) {
                body.html('<div class="alert alert-warning">No items found for this transfer.</div>');
                return;
            }

            const info = data[0];
            let statusClass = 'bg-warning';
            if (info.status === 'Completed') statusClass = 'bg-dark-green';
            if (info.status === 'Rejected') statusClass = 'bg-danger';

            let html = `
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3">
                            <div class="small text-muted text-uppercase fw-bold mb-1">Origin Branch</div>
                            <div class="fw-bold fs-5 text-dark-green">${info.from_branch}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 h-100">
                            <div class="small text-muted text-uppercase fw-bold mb-1">Destination Branch</div>
                            <div class="fw-bold fs-5 text-dark-green">${info.to_branch}</div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">ITEMS LIST</h6>
                    <span class="badge ${statusClass} px-3 py-2 text-white">${info.status}</span>
                </div>
                
                <div class="table-responsive border rounded">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="bg-dark-green text-white">
                            <tr>
                                <th class="ps-3 py-2 text-white">Part Details</th>
                                <th class="text-center py-2 text-white">Qty</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            data.forEach(item => {
                html += `
                    <tr>
                        <td class="ps-3">
                            <div class="fw-bold">${item.part_no}</div>
                            <div class="small text-muted">${item.description} | ${item.brand}</div>
                        </td>
                        <td class="text-center fw-bold fs-6">${item.qty}</td>
                    </tr>
                `;
            });

            html += `
                        </tbody>
                    </table>
                </div>
            `;

            body.html(html);

            // Handle Buttons based on status and branch
            const rejectBtn = $('#rejectTransferBtn');
            const receiveBtn = $('#confirmReceiveBtn');

            rejectBtn.addClass('d-none');
            receiveBtn.addClass('d-none');

            // If user is at destination branch and status is In-Transit, show receive/reject
            // (Note: in HO we might just want to view, but if it's HO's transfer, they can receive)
            if (info.status === 'In-Transit' && info.to_branch === window.currentBranch) {
                rejectBtn.removeClass('d-none');
                receiveBtn.removeClass('d-none');
            }
        });
    }

    // Global Transfers search
    $('#globalTransfersSearch').on('keyup', function () {
        const query = $(this).val().toLowerCase().trim();
        if (query === '') {
            renderGlobalTransfers(globalTransfersData);
        } else {
            const filtered = globalTransfersData.filter(t =>
                (t.id && t.id.toString().includes(query)) ||
                (t.from_branch && t.from_branch.toLowerCase().includes(query)) ||
                (t.to_branch && t.to_branch.toLowerCase().includes(query)) ||
                (t.transfer_date && t.transfer_date.includes(query))
            );
            renderGlobalTransfers(filtered);
        }
    });

    // Global Transfers refresh btn
    $('#refreshGlobalTransfersBtn').on('click', function () {
        $(this).find('i').addClass('fa-spin');
        loadGlobalTransfers();
        setTimeout(() => $(this).find('i').removeClass('fa-spin'), 1000);
    });

    function loadActivityLog() {
        loadApiData('get_activity_log', data => {
            activityLogData = data;
            renderActivityLog(data);
        }, 'activityLogTableBody');
    }

    function renderActivityLog(data) {
        const tbody = $('#activityLogTableBody');
        tbody.empty();

        if (!data || data.length === 0) {
            tbody.html('<tr><td colspan="5" class="text-center text-muted py-4">No activity logs found.</td></tr>');
            return;
        }

        data.forEach(log => {
            let actionBadge = '';
            switch (log.action_type.toLowerCase()) {
                case 'insert': actionBadge = '<span class="badge bg-success">CREATE</span>'; break;
                case 'update': actionBadge = '<span class="badge bg-primary">UPDATE</span>'; break;
                case 'delete': actionBadge = '<span class="badge bg-danger">DELETE</span>'; break;
                default: actionBadge = `<span class="badge bg-secondary">${log.action_type}</span>`;
            }

            tbody.append(`
            <tr class="align-middle">
                <td><div class="small fw-bold">${formatDateTime(log.action_timestamp)}</div></td>
                <td><span class="badge bg-light text-dark border">${escapeHtml(log.username)}</span></td>
                <td class="text-center">${actionBadge}</td>
                <td><div class="small fw-bold text-muted text-uppercase">${escapeHtml(log.table_name.replace('spareparts_', ''))}</div></td>
                <td><div class="small">${escapeHtml(log.action_details)}</div></td>
            </tr>
            `);
        });
    }

    function renderSaleDetails(data) {
        let itemsHtml = '';
        data.items.forEach(item => {
            itemsHtml += `
            <tr>
                <td><strong>${item.part_no}</strong><br><small>${item.description}</small></td>
                <td class="text-center">${item.quantity}</td>
                <td class="text-end">${formatCurrency(item.price)}</td>
                <td class="text-end fw-bold">${formatCurrency(item.total_amount)}</td>
            </tr>`;
        });

        $('#saleDetailsContent').html(`
            <div class="row mb-4">
            <div class="col-md-6">
                <div class="text-muted small text-uppercase fw-bold">Customer</div>
                <div class="fs-5 fw-bold">${escapeHtml(data.customer_name)}</div>
                <div class="text-muted small mt-1">OR #: <span class="">${escapeHtml(data.or_number)}</span></div>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <div class="text-muted small text-uppercase fw-bold">Transaction Details</div>
                <div>Date: <strong>${data.sale_date}</strong></div>
                <div>Type: <span class="badge ${data.transaction_type === 'charge' ? 'bg-info text-dark' : 'bg-success'}">${data.transaction_type.toUpperCase()}</span></div>
                <div>Branch: <span class="badge bg-secondary">${escapeHtml(data.branch)}</span></div>
            </div>
        </div>

            <table class="table table-bordered table-striped">
                <thead class="bg-light">
                    <tr>
                        <th>Item</th>
                        <th class="text-center">Qty</th>
                        <th class="text-end">Unit Price</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody>${itemsHtml}</tbody>
                <tfoot>
                    <tr class="table-light">
                        <td colspan="3" class="text-end fw-bold">GRAND TOTAL:</td>
                        <td class="text-end fw-bold fs-5 text-primary">₱${formatCurrency(data.total_amount)}</td>
                    </tr>
                    ${data.transaction_type === 'charge' ? `
                <tr class="table-danger">
                    <td colspan="3" class="text-end fw-bold text-danger">REMAINING BALANCE:</td>
                    <td class="text-end fw-bold fs-5 text-danger">₱${formatCurrency(data.balance)}</td>
                </tr>` : ''}
                </tfoot>
            </table>
        `);
    }

    function printAgingSummary() {
        const branch = $('#agingBranchFilter').val() || 'All';
        const search = $('#paymentsAgingSearch').val() || '';
        const title = `AGING SUMMARY REPORT - ${branch} `.toUpperCase();

        const printWindow = window.open('', '_blank');
        let tableHtml = `
        <table class="table table-bordered table-striped mt-4">
            <thead class="table-dark">
                <tr>
                    ${!window.isBranchPage ? '<th>BRANCH</th>' : ''}
                    <th>CUSTOMER NAME</th>
                    <th class="text-end">0-30 DAYS</th>
                    <th class="text-end">31-60 DAYS</th>
                    <th class="text-end">61-90 DAYS</th>
                    <th class="text-end">90+ DAYS</th>
                    <th class="text-end">TOTAL BALANCE</th>
                </tr>
            </thead>
            <tbody>
    `;

        let totalBalance = 0;
        let total30 = 0, total60 = 0, total90 = 0, total90p = 0;
        let rowCount = 0;

        $('#paymentsAgingTableBody tr.aging-row').each(function () {
            const rowDataBranch = $(this).data('branch') || '';
            const rowDataCustomer = $(this).data('customer') || '';

            const tdOffset = window.isBranchPage ? -1 : 0;

            const bal30 = parseFloat($(this).find(`td:nth-child(${4 + tdOffset})`).text().replace('₱', '').replace(/,/g, '')) || 0;
            const bal60 = parseFloat($(this).find(`td:nth-child(${5 + tdOffset})`).text().replace('₱', '').replace(/,/g, '')) || 0;
            const bal90 = parseFloat($(this).find(`td:nth-child(${6 + tdOffset})`).text().replace('₱', '').replace(/,/g, '')) || 0;
            const bal90p = parseFloat($(this).find(`td:nth-child(${7 + tdOffset})`).text().replace('₱', '').replace(/,/g, '')) || 0;
            const bal = parseFloat($(this).find(`td:nth-child(${8 + tdOffset})`).text().replace('₱', '').replace(/,/g, '')) || 0;

            totalBalance += bal;
            total30 += bal30;
            total60 += bal60;
            total90 += bal90;
            total90p += bal90p;
            rowCount++;

            tableHtml += `
            <tr>
                ${!window.isBranchPage ? `<td>${rowDataBranch}</td>` : ''}
                <td><b>${rowDataCustomer}</b></td>
                <td class="text-end">₱${formatCurrency(bal30)}</td>
                <td class="text-end">₱${formatCurrency(bal60)}</td>
                <td class="text-end">₱${formatCurrency(bal90)}</td>
                <td class="text-end text-danger">₱${formatCurrency(bal90p)}</td>
                <td class="text-end fw-bold">₱${formatCurrency(bal)}</td>
            </tr>
        `;
        });

        tableHtml += `
            </tbody>
            <tfoot class="fw-bold bg-light">
                <tr class="table-secondary">
                    <td colspan="${!window.isBranchPage ? '2' : '1'}" class="text-end">GRAND TOTALS:</td>
                    <td class="text-end">₱${formatCurrency(total30)}</td>
                    <td class="text-end">₱${formatCurrency(total60)}</td>
                    <td class="text-end">₱${formatCurrency(total90)}</td>
                    <td class="text-end text-danger">₱${formatCurrency(total90p)}</td>
                    <td class="text-end text-danger">₱${formatCurrency(totalBalance)}</td>
                </tr>
            </tfoot>
        </table>
        `;

        writePrintReport(printWindow, title, tableHtml, rowCount);
    }
    function printIndividualAging(customer, branch) {
        const title = `CUSTOMER STATEMENT OF ACCOUNT`.toUpperCase();
        const printWindow = window.open('', '_blank');

        $.get('../api/spareparts_inventory.php?action=get_customer_ledger', { customer: customer, branch: branch }, response => {
            if (response.success) {
                let tableHtml = `
            <div class="mb-4 border p-3 bg-light">
                <div class="row">
                    <div class="col-6">
                        <div class="small text-muted text-uppercase fw-bold">Customer</div>
                        <div class="fs-5 fw-bold">${customer}</div>
                    </div>
                    <div class="col-6 text-end">
                        <div class="small text-muted text-uppercase fw-bold">Branch</div>
                        <div class="fs-5 fw-bold">${branch}</div>
                    </div>
                </div>
                    </div>
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>DATE</th>
                        <th>OR # / REFERENCE</th>
                        <th class="text-end">DEBIT (SALES)</th>
                        <th class="text-end">CREDIT (PAYMENTS)</th>
                        <th class="text-end">BALANCE</th>
                    </tr>
                </thead>
                <tbody>
                    `;

                response.data.forEach(l => {
                    tableHtml += `
                        <tr>
                            <td>${l.date}</td>
                            <td>${l.or_number} <small class="text-muted">(${l.type})</small></td>
                            <td class="text-end">${l.sale_amount > 0 ? '₱' + formatCurrency(l.sale_amount) : '-'}</td>
                            <td class="text-end text-success">${l.payment_amount > 0 ? '₱' + formatCurrency(l.payment_amount) : '-'}</td>
                            <td class="text-end fw-bold ${l.balance > 0 ? 'text-danger' : 'text-success'}">₱${formatCurrency(l.balance)}</td>
                        </tr>
                    `;
                });

                tableHtml += `</tbody></table>`;
                writePrintReport(printWindow, title, tableHtml, response.data.length);
            }
        }, 'json');
    }

    function writePrintReport(printWindow, title, content, recordCount = 0) {
        const dateNow = new Date().toLocaleString();

        printWindow.document.write(`
                        <html>
                <head>
                    <title>${title}</title>
                    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'>
                    <style>
                    @page { size: landscape; margin: 1cm; }
                    body { font-family: 'Calibri', 'Arial', sans-serif; font-size: 11pt; padding: 20px; color: #000; }
                    .company-header { color: #000; border-bottom: 3px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
                    .report-info { font-size: 0.9rem; color: #333; margin-bottom: 10px; }
                    th { background-color: #f0f0f0 !important; color: #000 !important; font-size: 10pt; text-transform: uppercase; border: 1px solid #000 !important; }
                    td { font-size: 10pt; vertical-align: middle; border: 1px solid #000 !important; }
                    .table-dark { background-color: #f0f0f0 !important; color: #000 !important; }
                    .table-secondary { background-color: #f9f9f9 !important; }
                    .sig-line { border-bottom: 2px solid #000; width: 100%; margin-bottom: 5px; }

                    /* Print Pagination & Large Dataset Handling */
                    table { page-break-inside: auto; border-collapse: collapse; width: 100%; }
                    tr { page-break-inside: avoid; page-break-after: auto; }
                    thead { display: table-header-group; }
                    tfoot { display: table-footer-group; }
                </style>
                </head>
                <body>
                    <div class="company-header d-flex justify-content-between align-items-end">
                        <h1 class="mb-0"></h1>
                        <div class="text-end">
                            <h4 class="mb-0">${title}</h4>
                        </div>
                    </div>
                    <div class="report-info d-flex justify-content-between mt-2">
                        <span>Generated on: ${dateNow}</span>
                        <span>Record Count: ${recordCount}</span>
                    </div>
                    
                    ${content}

                    <div class="row mt-5 pt-4">
                        <div class="col-4">
                            <div class="sig-line"></div>
                            <div class="text-center small fw-bold">Prepared By</div>
                        </div>
                        <div class="col-4"></div>
                        <div class="col-4">
                            <div class="sig-line"></div>
                            <div class="text-center small fw-bold">Noted By</div>
                        </div>
                    </div>

                    <div class="mt-5 pt-4 text-center text-muted small border-top">
                        Spare Parts Management System &copy; ${new Date().getFullYear()}
                    </div>

                    <script>
                        window.onload = function() {
                            setTimeout(() => {
                                window.print();
                                // window.close();
                            }, 500);
                        };
                    </script>
                </body>
            </html>
            `);
        printWindow.document.close();
    }

    // Initial Load
    loadDashboardStats();
    loadInventory(); // Always load inventory (API handles filtering)
    setupEventListeners();
});
