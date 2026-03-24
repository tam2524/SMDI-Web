$(document).ready(function () {
    let inventoryData = [], salesData = [], paymentsData = [], transfersData = [], incomingTransfersData = [], activityLogData = [], globalTransfersData = [], paymentsAgingData = [];
    let inventoryCurrentPage = 1, salesCurrentPage = 1, paymentsCurrentPage = 1, transfersCurrentPage = 1, incomingTransfersCurrentPage = 1;
    let inventoryFilteredData = [], salesFilteredData = [], transfersAllData = [], incomingTransfersAllData = [], paymentsAllData = [];
    let saleCart = [], transferCart = [];
    const isBranchPage = typeof window.isBranchPage !== 'undefined' ? window.isBranchPage : window.location.pathname.includes('warehouse_spareparts');
    const isAdminPage = window.location.pathname.includes('admin_spareparts') || window.location.pathname.includes('headoffice_spareparts');
    const canDelete = typeof window.canDelete !== 'undefined' ? window.canDelete : false;
    const PAGE_SIZE = 10;

    // Utility Functions
    function formatCurrency(amount) { return Number(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function formatDateTime(dateStr) { if (!dateStr) return 'N/A'; const d = new Date(dateStr); return d.toLocaleString('en-US', { year: 'numeric', month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit' }); }
    function escapeHtml(text) { const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }; return String(text || '').replace(/[&<>"']/g, m => map[m]); }
    function filterData(data, query, fields) { if (!query) return data; const q = query.toLowerCase(); return data.filter(item => fields.some(field => item[field] && String(item[field]).toLowerCase().includes(q))); }

    // Auto-fill all date pickers with today's date
    function autoFillDates(container = document) {
        const today = new Date();
        const yyyy = today.getFullYear();
        let mm = today.getMonth() + 1;
        let dd = today.getDate();
        if (dd < 10) dd = '0' + dd;
        if (mm < 10) mm = '0' + mm;
        const formattedDate = `${yyyy}-${mm}-${dd}`;

        $(container).find('input[type="date"], input[type="datetime-local"]').each(function () {
            if (!$(this).val()) {
                if ($(this).attr('type') === 'date') {
                    $(this).val(formattedDate);
                } else if ($(this).attr('type') === 'datetime-local') {
                    let hh = today.getHours();
                    let min = today.getMinutes();
                    if (hh < 10) hh = '0' + hh;
                    if (min < 10) min = '0' + min;
                    $(this).val(`${formattedDate}T${hh}:${min}`);
                }
            }
        });
    }

    // Initial call and hook for dynamic content (modals)
    autoFillDates();
    $(document).on('shown.bs.modal', function (e) {
        autoFillDates(e.target);
    });

    function renderPagination(pagId, infoId, totalItems, currentPage, onChangePage) {
        const totalPages = Math.max(1, Math.ceil(totalItems / PAGE_SIZE));
        const pag = document.getElementById(pagId);
        const info = document.getElementById(infoId);
        if (!pag || !info) return;

        const start = totalItems === 0 ? 0 : (currentPage - 1) * PAGE_SIZE + 1;
        const end = Math.min(currentPage * PAGE_SIZE, totalItems);
        info.textContent = totalItems === 0 ? 'No items found' : `Showing ${start}-${end} of ${totalItems} items`;

        pag.innerHTML = '';
        if (totalPages <= 1) return;

        const mkItem = (label, pageNumber, disabled, active) => {
            const li = document.createElement('li');
            li.className = `page-item ${disabled ? 'disabled' : ''} ${active ? 'active' : ''}`;
            const a = document.createElement('a');
            a.className = `page-link rounded-pill px-3 ${active ? 'text-white' : ''}`;
            a.href = '#';
            a.dataset.page = pageNumber;
            a.style.cssText = active ? 'background:var(--smdi-green);border-color:var(--smdi-green);' : 'border-color:#dee2e6;';
            a.innerHTML = label;
            li.appendChild(a);
            return li;
        };

        pag.appendChild(mkItem('<i class="bi bi-chevron-left"></i>', currentPage - 1, currentPage === 1, false));

        const delta = 2;
        const rangeStart = Math.max(1, currentPage - delta);
        const rangeEnd = Math.min(totalPages, currentPage + delta);

        if (rangeStart > 1) {
            pag.appendChild(mkItem('1', 1, false, false));
            if (rangeStart > 2) {
                const li = document.createElement('li');
                li.className = 'page-item disabled';
                li.innerHTML = '<span class="page-link px-2 border-0 bg-transparent">...</span>';
                pag.appendChild(li);
            }
        }

        for (let p = rangeStart; p <= rangeEnd; p++) {
            pag.appendChild(mkItem(p, p, false, p === currentPage));
        }

        if (rangeEnd < totalPages) {
            if (rangeEnd < totalPages - 1) {
                const li = document.createElement('li');
                li.className = 'page-item disabled';
                li.innerHTML = '<span class="page-link px-2 border-0 bg-transparent">...</span>';
                pag.appendChild(li);
            }
            pag.appendChild(mkItem(totalPages, totalPages, false, false));
        }

        pag.appendChild(mkItem('<i class="bi bi-chevron-right"></i>', currentPage + 1, currentPage === totalPages, false));

        $(pag).off('click', '.page-link').on('click', '.page-link', function (e) {
            e.preventDefault();
            const pg = parseInt($(this).data('page'));
            if (pg && pg >= 1 && pg <= totalPages && pg !== currentPage) {
                onChangePage(pg);
            }
        });
    }







    // Core Loading Logic
    function loadApiData(endpoint, successCallback, tableBodyId = null, data = {}) {
        if (tableBodyId) $(`#${tableBodyId}`).html(`<tr><td colspan="12" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>`);

        $.ajax({
            url: `../api/spareparts_inventory.php?action=${endpoint}`,
            method: 'GET',
            data: data,
            dataType: 'json',
            cache: false,
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
        $('button[data-bs-toggle="tab"], button[data-bs-toggle="pill"]').on('shown.bs.tab', function (e) {
            const targetTab = this.id || $(e.target).attr('id');
            const targetPane = $(e.target).attr('data-bs-target');

            switch (targetTab) {
                case 'inventory-tab':
                case 'sc-profile-tab': loadInventory(); break;
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
                    case '#sc-profile': /* Landing page */ break;
                    case '#sub-stock': loadInventory(); break;
                    case '#sub-sales': loadSales(); break;
                    case '#sub-payments': loadPayments(); break;
                    case '#sub-transfer-out': loadTransfers(); break;
                    case '#sub-transfer-in': loadIncomingTransfers(); break;
                }
            }
        });

        // Landing Page Search Handler
        $('#landingStockSearchBtn').on('click', function () {
            const val = $('#landingStockSearch').val().trim();
            if (val && typeof showStockCard === 'function') showStockCard(val);
        });

        $('#landingStockSearch').on('keypress', function (e) {
            if (e.which === 13) {
                const val = $(this).val().trim();
                if (val && typeof showStockCard === 'function') showStockCard(val);
            }
        });

        // Search Handlers
        $('#salesSearch').on('input', e => { salesCurrentPage = 1; renderSales(filterData(salesData, $(e.target).val(), ['customer_name', 'or_number'])); });
        $('#paymentsSearch').on('input', e => { paymentsCurrentPage = 1; renderPayments(filterData(paymentsData, $(e.target).val(), ['customer_name', 'or_number'])); });
        $('#transfersSearch').on('input', e => { transfersCurrentPage = 1; renderTransfers(filterData(transfersData, $(e.target).val(), ['from_branch', 'to_branch'])); });
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
            salesCurrentPage = 1; // Reset to page 1 on filter change
            renderSales(salesData, type);
        });

        // Sales New Filters
        $('#applySalesFiltersBtn').on('click', function () {
            salesCurrentPage = 1;
            loadSales();
        });

        $('#resetSalesFiltersBtn').on('click', function () {
            $('#salesFilterBranch').val('all');
            $('#salesFilterDateFrom').val('');
            $('#salesFilterDateTo').val('');
            salesCurrentPage = 1;
            loadSales();
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

                    const reportSelectors = '#inv_report_branch, #sales_report_branch, #payment_report_branch, #t_report_branch, #salesFilterBranch';
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
            $('#edit_stock').val(item.current_stock).data('initial', item.current_stock);
            $('#edit_description').val(item.description);
            $('#edit_cost').val(item.cost);
            $('#edit_price').val(item.price);
            $('#edit_min_stock').val(item.min_stock);
            $('#edit_bin_location').val(item.bin_location || '');
            $('#edit_invoice_no').val(item.invoice_no || '');
            $('#edit_change_reason').val('');
            $('#edit_part_image').val(''); // Clear old file selection

            $('#edit_reason_container').hide();
            bootstrap.Modal.getOrCreateInstance(document.getElementById('editPartModal')).show();
        });

        $(document).on('input', '#edit_stock', function () {
            const initial = $(this).data('initial');
            const current = $(this).val();
            if (initial != current) {
                $('#edit_reason_container').slideDown();
                $('#edit_change_reason').prop('required', true);
            } else {
                $('#edit_reason_container').slideUp();
                $('#edit_change_reason').prop('required', false);
            }
        });

        $(document).on('click', '.view-history-btn', function () {
            const id = $(this).data('id');
            const item = inventoryData.find(i => i.id == id);
            if (!item) return;

            $('#historyPartDescription').text(item.description);
            $('#historyPartBrand').text(item.brand);
            $('#historyPartNumber').text(item.part_no);
            $('#historyTableBody').html('<tr><td colspan="6" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div> Loading...</td></tr>');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('viewHistoryModal')).show();

            loadApiData('get_inventory_history', data => {
                renderInventoryHistory(data);
            }, 'historyTableBody', { id: id });
        });

        $(document).on('click', '.show-stock-card-btn', function () {
            const partNo = $(this).data('part-no');
            const branch = $(this).data('branch');
            if (typeof showStockCard === 'function') {
                showStockCard(partNo, branch);
            } else {
                console.error('showStockCard is not defined. Ensure spareparts_stock_card.js is included.');
            }
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

        let editSaleCart = [];

        $(document).on('click', '.edit-sale-btn', function () {
            const or = $(this).data('or');
            const branch = $(this).data('branch');

            // Clear previous data
            $('#editSaleItemsBody').empty();
            $('#edit_sale_reason').val('');
            $('#edit_sale_total_display').text('₱0.00');
            $('#editSaleItemSearch').val('');
            $('#edit_sale_sales_force').val('');

            // Show loading state or modal immediately
            const editModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('editSaleModal'));
            editModal.show();

            $.get('../api/spareparts_inventory.php?action=get_sale_details', { or_number: or, branch: branch }, response => {
                if (response.success) {
                    const data = response.data;

                    // Populate meta
                    $('#edit_sale_original_or').val(data.or_number);
                    $('#edit_sale_original_branch').val(data.branch);
                    $('#edit_sale_or').val(data.or_number);
                    $('#edit_sale_customer').val(data.customer_name);
                    $('#edit_sale_date').val(data.sale_date);
                    $('#edit_sale_type').val(data.transaction_type);
                    $('#edit_sale_sales_force').val(data.sales_force || '');

                    // Populate branch select
                    const branchSelect = $('#edit_sale_branch');
                    if (branchSelect.find('option').length <= 0) {
                        $.get('../api/spareparts_inventory.php?action=get_branches', res => {
                            if (res.success) {
                                let html = '';
                                res.data.forEach(b => html += `<option value="${b}">${b}</option>`);
                                branchSelect.html(html).val(data.branch);
                            }
                        }, 'json');
                    } else {
                        branchSelect.val(data.branch);
                    }

                    // Populate items
                    editSaleCart = data.items.map(item => ({
                        part_no: item.part_no,
                        description: item.description,
                        quantity: parseInt(item.quantity),
                        price: parseFloat(item.price)
                    }));
                    renderEditSaleItems();
                } else {
                    showErrorModal(response.message);
                    editModal.hide();
                }
            }, 'json');
        });

        function renderEditSaleItems() {
            const tbody = $('#editSaleItemsBody');
            tbody.empty();
            let total = 0;

            if (editSaleCart.length === 0) {
                tbody.html('<tr><td colspan="5" class="text-center text-muted py-4">No items in this sale.</td></tr>');
            } else {
                editSaleCart.forEach((item, index) => {
                    const subtotal = item.quantity * item.price;
                    total += subtotal;
                    tbody.append(`
                        <tr>
                            <td class="ps-3 py-2">
                                <div class="fw-bold">${escapeHtml(item.part_no)}</div>
                                <div class="small text-muted">${escapeHtml(item.description)}</div>
                            </td>
                            <td>
                                <input type="number" class="form-control form-control-sm text-center edit-item-qty" 
                                    data-index="${index}" value="${item.quantity}" min="1">
                            </td>
                            <td>
                                <input type="number" step="0.01" class="form-control form-control-sm text-end edit-item-price" 
                                    data-index="${index}" value="${item.price}">
                            </td>
                            <td class="text-end fw-bold">₱${formatCurrency(subtotal)}</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-edit-item" data-index="${index}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `);
                });
            }

            $('#edit_sale_total_display').text(`₱${formatCurrency(total)}`);
            $('#edit_sale_total_input').val(total);
        }

        // Handle item qty/price change in edit modal
        $(document).on('change', '.edit-item-qty', function () {
            const idx = $(this).data('index');
            editSaleCart[idx].quantity = parseInt($(this).val()) || 1;
            renderEditSaleItems();
        });

        $(document).on('change', '.edit-item-price', function () {
            const idx = $(this).data('index');
            editSaleCart[idx].price = parseFloat($(this).val()) || 0;
            renderEditSaleItems();
        });

        $(document).on('click', '.remove-edit-item', function () {
            const idx = $(this).data('index');
            editSaleCart.splice(idx, 1);
            renderEditSaleItems();
        });

        // Search items to add to edit sale
        $('#editSaleItemSearch').on('input', function () {
            const query = $(this).val();
            if (query.length < 2) { $('#editSaleItemResults').hide(); return; }
            $.get('../api/spareparts_inventory.php?action=search_inventory_parts', { term: query }, res => {
                if (res.success && res.data.length > 0) {
                    let html = '';
                    res.data.forEach(item => {
                        html += `<button type="button" class="list-group-item list-group-item-action add-item-to-edit" 
                            data-item='${JSON.stringify(item).replace(/'/g, "&apos;")}'>
                            <div class="d-flex justify-content-between align-items-center">
                                <div><strong>${item.part_no}</strong><br><small>${item.description}</small></div>
                                <div class="text-end small">Stock: <span class="badge bg-light text-dark border">${item.current_stock}</span></div>
                            </div>
                        </button>`;
                    });
                    $('#editSaleItemResults').html(html).show();
                } else {
                    $('#editSaleItemResults').hide();
                }
            }, 'json');
        });

        $(document).on('click', '.add-item-to-edit', function () {
            const item = $(this).data('item');
            const existing = editSaleCart.find(i => i.part_no === item.part_no);
            if (existing) {
                existing.quantity += 1;
            } else {
                editSaleCart.push({
                    part_no: item.part_no,
                    description: item.description,
                    quantity: 1,
                    price: parseFloat(item.price)
                });
            }
            $('#editSaleItemResults').hide();
            $('#editSaleItemSearch').val('');
            renderEditSaleItems();
        });

        // Search Sales Force in edit modal
        $('#edit_sale_sales_force').on('input focus', function () {
            const query = $(this).val();
            $.get('../api/spareparts_inventory.php?action=get_sales_force', res => {
                if (res.success) {
                    let filtered = res.data;
                    if (query) {
                        filtered = res.data.filter(e => e.employee_name.toLowerCase().includes(query.toLowerCase()));
                    }
                    if (filtered.length > 0) {
                        let html = '';
                        filtered.forEach(e => {
                            html += `<button type="button" class="list-group-item list-group-item-action select-sf-edit" 
                                data-name="${escapeHtml(e.employee_name)}">${escapeHtml(e.employee_name)}</button>`;
                        });
                        $('#editSaleForceResults').html(html).show();
                    } else {
                        $('#editSaleForceResults').hide();
                    }
                }
            }, 'json');
        });

        $(document).on('click', '.select-sf-edit', function () {
            $('#edit_sale_sales_force').val($(this).data('name'));
            $('#editSaleForceResults').hide();
        });

        // Hide search results when clicking outside
        $(document).on('click', function (e) {
            if (!$(e.target).closest('#editSaleItemSearch, #editSaleItemResults').length) $('#editSaleItemResults').hide();
            if (!$(e.target).closest('#edit_sale_sales_force, #editSaleForceResults').length) $('#editSaleForceResults').hide();
        });

        $(document).on('click', '.view-sale-btn', function () {
            const or = $(this).data('or');
            const branch = $(this).data('branch');
            $('#saleDetailsContent').html('<div class="text-center p-5"><div class="spinner-border text-primary"></div><p class="mt-2">Loading sale details...</p></div>');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('viewSaleDetailsModal')).show();

            $.get('../api/spareparts_inventory.php?action=get_sale_details', { or_number: or, branch: branch }, response => {
                if (response.success) {
                    renderSaleDetails(response.data);
                } else {
                    showErrorModal(response.message);
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('viewSaleDetailsModal')).hide();
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
        // ADD PART LOGIC
        $('#addPartForm').on('submit', function (e) {
            e.preventDefault();
            const btn = $(this).find('button[type="submit"]');
            const originalText = btn.html();
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');

            const formData = new FormData(this);
            $.ajax({
                url: '../api/spareparts_inventory.php?action=add_part',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (res) {
                    btn.prop('disabled', false).html(originalText);
                    if (res.success) {
                        $('#addPartModal').modal('hide');
                        $('#addPartForm')[0].reset();
                        showSuccessModal('Part registered successfully!');
                        loadInventory(); // Reload the table
                    } else {
                        showErrorModal(res.message || 'Error occurred.');
                    }
                },
                error: function () {
                    btn.prop('disabled', false).html(originalText);
                    showErrorModal('Connection error.');
                }
            });
        });

        $('#editPartForm').on('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            $.ajax({
                url: '../api/spareparts_inventory.php?action=edit_parts',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: response => {
                    let res = response;
                    if (typeof response === 'string') {
                        try { res = JSON.parse(response); } catch (e) { }
                    }
                    if (res.success) {
                        bootstrap.Modal.getOrCreateInstance(document.getElementById('editPartModal')).hide();
                        showSuccessModal('Part updated successfully.');
                        loadInventory();
                    } else {
                        showErrorModal(res.message);
                    }
                }
            });
        });

        $('#editSaleForm').on('submit', function (e) {
            e.preventDefault();
            if (editSaleCart.length === 0) {
                showErrorModal('Sale must have at least one item.');
                return;
            }

            const btn = $(this).find('button[type="submit"]');
            const originalHtml = btn.html();
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');

            const formData = $(this).serializeArray();
            formData.push({ name: 'items', value: JSON.stringify(editSaleCart) });

            $.post('../api/spareparts_inventory.php?action=edit_sale', formData, response => {
                if (response.success) {
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('editSaleModal')).hide();
                    showSuccessModal(response.message);
                    loadSales();
                    loadInventory(); // Reload inventory as levels might have shifted
                } else {
                    showErrorModal(response.message);
                }
            }, 'json').always(() => btn.prop('disabled', false).html(originalHtml));
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
                        bootstrap.Modal.getOrCreateInstance(document.getElementById('addPartsInModal')).hide();
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
                transferCart.push({ ...item, quantity: 1, cost: item.cost || 0 });
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
                tbody.html('<tr id="emptyTransferListRow"><td colspan="4" class="text-center text-muted p-5">Cart is empty</td></tr>');
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
                            <td>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" step="0.01" class="form-control form-control-sm text-end transfer-cost-input" 
                                        data-index="${index}" value="${item.cost}" min="0">
                                </div>
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
            let qty = parseInt($(this).val());
            const max = parseInt($(this).attr('max'));
            if (qty > max) { qty = max; $(this).val(max); }
            if (qty < 1 || isNaN(qty)) { qty = 1; $(this).val(1); }
            transferCart[index].quantity = qty;
        });

        $(document).on('change', '.transfer-cost-input', function () {
            const index = $(this).data('index');
            let cost = parseFloat($(this).val());
            if (cost < 0 || isNaN(cost)) { cost = 0; $(this).val(0); }
            transferCart[index].cost = cost;
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
                transfer_no: $('#transfer_no').val() || '',
                items: JSON.stringify(transferCart.map(p => ({
                    part_no: p.part_no,
                    description: p.description,
                    quantity: p.quantity,
                    cost: p.cost
                })))
            }, response => {
                if (response.success) {
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('transferPartsModal')).hide();
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
            bootstrap.Modal.getOrCreateInstance(document.getElementById('viewTransferDetailsModal')).show();

            $.get('../api/spareparts_inventory.php?action=get_transfer_details', { id: id }, response => {
                if (response.success) {
                    renderTransferDetails(response.data);
                } else {
                    showErrorModal(response.message);
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('viewTransferDetailsModal')).hide();
                }
            }, 'json');
        });

        function renderTransferDetails(data) {
            const getStatusBadge = (status) => {
                if (!status) return '<span class="badge bg-secondary">UNKNOWN</span>';
                const colors = {
                    'In-Transit': 'bg-warning text-dark',
                    'Completed': 'bg-success text-white',
                    'Rejected': 'bg-danger text-white',
                    'Pending': 'bg-info text-white'
                };
                return `<span class="badge ${colors[status] || 'bg-secondary'} px-3 py-2 rounded-pill shadow-sm fw-bold border border-white border-opacity-25">${status.toUpperCase()}</span>`;
            };

            let itemsHtml = '';
            data.items.forEach(item => {
                itemsHtml += `
                    <tr>
                        <td class="ps-4 py-3">
                            <div class="fw-bold text-dark-green mb-1">${item.part_no}</div>
                            <div class="small text-muted fw-500">${item.description || 'No description provided'}</div>
                        </td>
                        <td class="text-center fw-bold align-middle ps-0 pe-4" style="width: 120px; color: var(--sp-green);">
                            <div class="bg-light rounded-3 py-2 border shadow-sm fs-5">${item.quantity}</div>
                        </td>
                    </tr>`;
            });

            $('#transferDetailsBody').html(`
                <div class="bg-white border-bottom shadow-sm overflow-hidden">
                    <div class="bg-dark-green text-white p-4 py-3">
                        <div class="d-flex justify-content-between align-items-center">
                             <div class="d-flex align-items-center gap-3">
                                <i class="bi bi-file-earmark-text fs-4 text-white-50"></i>
                                <div>
                                    <h4 class="fw-bold mb-0 text-white ls-1">TRANSFER RECORD</h4>
                                    <div class="small text-white-50 fw-bold">REF No. ${data.id.toString().padStart(6, '0')} ${data.transfer_no ? `| <span class="text-white">NO. ${data.transfer_no}</span>` : ''}</div>
                                </div>
                             </div>
                             ${getStatusBadge(data.status)}
                        </div>
                    </div>

                    <div class="p-4 bg-light bg-opacity-50">
                        <div class="row g-4 align-items-stretch">
                            <div class="col-md-5">
                                <div class="card h-100 border-0 shadow-sm overflow-hidden border-start border-4" style="border-color: var(--sp-green-light) !important;">
                                    <div class="card-body p-3">
                                        <div class="small fw-bold text-muted text-uppercase mb-2"><i class="bi bi-geo-alt-fill me-1" style="color: var(--sp-green-light);"></i>From Branch</div>
                                        <div class="h5 mb-0 fw-bold text-dark">${data.from_branch}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2 d-none d-md-flex align-items-center justify-content-center">
                                <div class="bg-white rounded-circle shadow-sm border p-2">
                                    <i class="bi bi-arrow-right-short fs-2" style="color: var(--sp-green);"></i>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="card h-100 border-0 shadow-sm overflow-hidden border-start border-4 border-success">
                                    <div class="card-body p-3 text-md-end">
                                        <div class="small fw-bold text-muted text-uppercase mb-2"><i class="bi bi-geo-fill me-1 text-success"></i>Destination</div>
                                        <h5 class="mb-0 fw-bold text-dark">${data.to_branch}</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-4 py-3 bg-white border-top border-bottom">
                         <div class="row align-items-center">
                            <div class="col-sm-6 border-end text-center text-sm-start">
                                <span class="text-muted small fw-bold text-uppercase d-block mb-1">Date of Transfer</span>
                                <span class="fw-bold h6 mb-0 text-dark"><i class="bi bi-calendar-check me-2" style="color: var(--sp-green);"></i>${data.transfer_date}</span>
                            </div>
                            <div class="col-sm-6 text-center text-sm-end">
                                <span class="text-muted small fw-bold text-uppercase d-block mb-1">Inventory Impact</span>
                                <span class="fw-bold h6 mb-0" style="color: var(--sp-green);"><i class="bi bi-box-seam me-2"></i>${data.items ? data.items.length : 0} Unique Parts</span>
                            </div>
                         </div>
                    </div>
                </div>

                <div class="p-0">
                    <div class="bg-dark-green text-white p-2 px-4 small fw-bold text-uppercase d-flex justify-content-between border-bottom" style="opacity: 0.9;">
                        <span class="ls-1">Dispatched Item Details</span>
                        <span class="ls-1">Quantity</span>
                    </div>
                    <div class="table-responsive border-bottom" style="max-height: 480px;">
                        <table class="table table-hover align-middle mb-0 border-0">
                            <tbody id="transferItemsList" class="bg-white">
                                ${itemsHtml}
                            </tbody>
                        </table>
                    </div>
                </div>
            `);

            // Only show actions for In-Transit transfers based on branch
            $('#rejectTransferBtn, #confirmReceiveBtn, #cancelTransferBtn').addClass('d-none');
            if (data.status === 'In-Transit') {
                if (data.to_branch === window.currentBranch) {
                    $('#rejectTransferBtn, #confirmReceiveBtn').removeClass('d-none');
                } else if (data.from_branch === window.currentBranch) {
                    $('#cancelTransferBtn').removeClass('d-none');
                }
            }
        }

        $('#confirmReceiveBtn').on('click', function () {
            showConfirmModal('Are you sure you want to receive these items?', function () {
                $.post('../api/spareparts_inventory.php?action=accept_transfer', { id: currentViewingTransferId }, response => {
                    if (response.success) {
                        bootstrap.Modal.getOrCreateInstance(document.getElementById('viewTransferDetailsModal')).hide();
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
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('viewTransferDetailsModal')).hide();
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

        $('#cancelTransferBtn').on('click', function () {
            if (!currentViewingTransferId) return;
            showConfirmModal('Cancel this transfer? Inventory will be returned to your branch.', function () {
                $.post('../api/spareparts_inventory.php?action=delete_transfer', { id: currentViewingTransferId }, response => {
                    if (response.success) {
                        bootstrap.Modal.getOrCreateInstance(document.getElementById('viewTransferDetailsModal')).hide();
                        showSuccessModal('Transfer cancelled successfully.');
                        loadInventory();
                        loadTransfers();
                    } else {
                        showErrorModal(response.message);
                    }
                }, 'json');
            });
        });

        // POS LOGIC (Sales Out)
        let currentCustomerRank = 'Standard';
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
                        const custName = item.customer_name;
                        const rank = item.rank_level || 'Standard';
                        if (!custName) return;
                        html += `<button type="button" class="list-group-item list-group-item-action fw-bold fill-sale-customer" 
                                    data-name="${escapeHtml(custName)}" data-rank="${rank}">
                                    <i class="bi bi-person text-secondary me-2"></i>${escapeHtml(custName)}
                                    <span class="badge bg-light text-dark border ms-2 small fw-normal">${rank}</span>
                                 </button>`;
                    });
                    resultsBox.html(html).show();
                } else {
                    resultsBox.hide();
                }
            }, 'json');
        });

        $(document).on('click', '.fill-sale-customer', function () {
            const name = $(this).data('name');
            const rank = $(this).data('rank');
            $('#out_customer_name').val(name);
            currentCustomerRank = rank;
            $('#saleCustomerSearchResults').hide();
            updateCartPricesForRank();
        });

        function updateCartPricesForRank() {
            if (saleCart.length === 0) return;
            saleCart.forEach(item => {
                $.get('../api/spareparts_inventory.php?action=get_rank_price', { part_no: item.part_no, rank_level: currentCustomerRank }, function (res) {
                    if (res.success) {
                        item.price = parseFloat(res.price);
                        renderSaleCart();
                    }
                }, 'json');
            });
        }

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
            // Clear sales force field on new sale
            $('#out_sales_force').val('');
            $('#salesForceSearchResults').hide();
        });

        // ---- SALES FORCE AUTOCOMPLETE ----
        $('#out_sales_force').on('input', function () {
            const term = $(this).val().trim();
            const $results = $('#salesForceSearchResults');
            if (term.length < 1) { $results.hide(); return; }
            $.get('../api/spareparts_inventory.php?action=search_sales_force', { term }, function (res) {
                $results.empty();
                if (res.success && res.data.length > 0) {
                    res.data.forEach(emp => {
                        $results.append(`<button type="button" class="list-group-item list-group-item-action sf-pick" data-name="${emp.employee_name}">
                            <i class="bi bi-person me-2 text-success"></i>${emp.employee_name}
                        </button>`);
                    });
                    $results.show();
                } else {
                    $results.hide();
                }
            }, 'json');
        });

        $(document).on('click', '.sf-pick', function () {
            $('#out_sales_force').val($(this).data('name'));
            $('#salesForceSearchResults').hide();
        });

        $(document).on('click', function (e) {
            if (!$(e.target).closest('#out_sales_force, #salesForceSearchResults').length) {
                $('#salesForceSearchResults').hide();
            }
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
            const existing = saleCart.find(p => String(p.id) === String(item.id));
            if (existing) {
                if (existing.quantity < item.current_stock) {
                    existing.quantity++;
                    renderSaleCart();
                }
            } else {
                // Instantly add to cart with default price for better UX
                const newItem = { ...item, quantity: 1 };
                saleCart.push(newItem);
                renderSaleCart();

                // Then fetch rank-based price if available
                $.get('../api/spareparts_inventory.php?action=get_rank_price', {
                    part_no: item.part_no,
                    rank_level: currentCustomerRank || 'Standard'
                }, function (res) {
                    if (res.success) {
                        const updated = saleCart.find(p => String(p.id) === String(item.id));
                        if (updated) {
                            updated.price = parseFloat(res.price);
                            renderSaleCart();
                        }
                    }
                }, 'json').fail(function () {
                    console.warn("Could not fetch rank price for " + item.part_no);
                });
            }
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
                            <td>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" step="0.01" class="form-control form-control-sm text-end sale-price-input" 
                                        data-index="${index}" value="${parseFloat(item.price).toFixed(2)}">
                                </div>
                            </td>
                            <td class="text-end fw-bold">₱${formatCurrency(subtotal)}</td>
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
            let qty = parseInt($(this).val());
            const max = parseInt($(this).attr('max'));
            if (qty > max) { qty = max; $(this).val(max); }
            if (qty < 1 || isNaN(qty)) { qty = 1; $(this).val(1); }
            saleCart[index].quantity = qty;
            renderSaleCart();
        });

        $(document).on('change', '.sale-price-input', function () {
            const index = $(this).data('index');
            let price = parseFloat($(this).val());
            if (price < 0 || isNaN(price)) { price = 0; $(this).val(0); }
            saleCart[index].price = price;
            renderSaleCart();
        });

        $('#out_transaction_type').on('change', function() {
            if ($(this).val() === 'pdc') {
                $('#pdc_fields').removeClass('d-none');
            } else {
                $('#pdc_fields').addClass('d-none');
            }
        });

        $('#sellPartsOutForm').on('submit', function (e) {
            e.preventDefault();
            if (saleCart.length === 0) { showErrorModal('Add items to sale first.'); return; }

            const btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true).text('Confirming...');

            const transactionTypeVal = $('#out_transaction_type').val();
            const payment_method = (transactionTypeVal === 'pdc') ? 'PDC' : (transactionTypeVal === 'charge' ? 'Charge' : 'Cash');
            const check_date = $('#out_check_date').val();

            const saleData = {
                or_number: $('#out_or_number').val(),
                customer_name: $('#out_customer_name').val(),
                date: $('#out_date').val(),
                transaction_type: (transactionTypeVal === 'pdc' ? 'charge' : transactionTypeVal),
                payment_method: payment_method,
                check_date: check_date,
                sales_force: $('#out_sales_force').val().trim(),
                items: JSON.stringify(saleCart.map(p => ({ id: p.id, part_no: p.part_no, description: p.description, quantity: p.quantity, price: p.price })))
            };

            $.post('../api/spareparts_inventory.php?action=sell_multiple_parts_out', saleData, response => {
                if (response.success) {
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('sellPartsOutModal')).hide();

                    // Generate and show receipt
                    renderSaleReceipt(saleData, saleCart);
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('receiptModal')).show();

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

        function renderSaleReceipt(data, items) {
            let grandTotal = 0;
            let itemsHtml = '';
            items.forEach(item => {
                const subtotal = item.quantity * item.price;
                grandTotal += subtotal;
                itemsHtml += `
                    <tr>
                        <td style="padding: 5px 0;">
                            <div style="font-weight: bold;">${item.part_no}</div>
                            <div style="font-size: 0.8rem; color: #666;">${item.description}</div>
                        </td>
                        <td style="text-align: center; padding: 5px 0;">${item.quantity}</td>
                        <td style="text-align: right; padding: 5px 0;">₱${formatCurrency(item.price)}</td>
                        <td style="text-align: right; padding: 5px 0; font-weight: bold;">₱${formatCurrency(subtotal)}</td>
                    </tr>
                `;
            });

            const html = `
                <div style="font-family: 'Courier New', Courier, monospace; color: #000;">
                    <div style="text-align: center; margin-bottom: 20px;">
                        <h4 style="margin: 0; font-weight: bold;">Roxas City Solid Merchandising</h4>
                        <p style="margin: 5px 0; font-size: 0.9rem;">Sales Invoice</p>
                        <div style="border-top: 1px dashed #000; margin: 10px 0;"></div>
                    </div>
                    
                    <div style="margin-bottom: 15px; font-size: 0.9rem;">
                        <div style="display: flex; justify-content: space-between;">
                            <span>SI NO:</span>
                            <span style="font-weight: bold;">${data.or_number}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>Customer:</span>
                            <span style="font-weight: bold;">${data.customer_name || '-'}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>Date:</span>
                            <span>${data.date}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>Payment:</span>
                            <span style="text-transform: uppercase;">${data.transaction_type}</span>
                        </div>
                    </div>

                    <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem; margin-bottom: 15px;">
                        <thead>
                            <tr style="border-bottom: 1px solid #000; text-align: left;">
                                <th style="padding-bottom: 5px;">Item</th>
                                <th style="text-align: center; padding-bottom: 5px;">Qty</th>
                                <th style="text-align: right; padding-bottom: 5px;">Price</th>
                                <th style="text-align: right; padding-bottom: 5px;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${itemsHtml}
                        </tbody>
                    </table>

                    <div style="border-top: 1px dashed #000; padding-top: 10px;">
                        <div style="display: flex; justify-content: space-between; font-size: 1.1rem; font-weight: bold;">
                            <span>GRAND TOTAL:</span>
                            <span>₱${formatCurrency(grandTotal)}</span>
                        </div>
                    </div>

                    <div style="margin-top: 30px; text-align: center; font-size: 0.8rem;">
                        <p style="margin: 5px 0;">Thank you for your order!</p>
                        <div style="margin-top: 15px;">
                            <p style="margin: 0; border-top: 1px solid #000; display: inline-block; padding-top: 5px; width: 150px;">Customer Signature</p>
                        </div>
                    </div>
                </div>
            `;
            $('#receiptContent').html(html);
        }

        function renderPaymentReceipt(data) {
            const html = `
                <div style="font-family: 'Courier New', Courier, monospace; color: #000;">
                    <div style="text-align: center; margin-bottom: 20px;">
                        <h4 style="margin: 0; font-weight: bold;">Roxas City Solid Merchandising</h4>
                        <p style="margin: 5px 0; font-size: 0.9rem;">Official Payment Receipt</p>
                        <div style="border-top: 1px dashed #000; margin: 10px 0;"></div>
                    </div>
                    
                    <div style="margin-bottom: 20px; font-size: 1rem;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span>Receipt NO:</span>
                            <span style="font-weight: bold;">${data.or_number}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span>Date:</span>
                            <span>${data.date}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span>Customer:</span>
                            <span style="font-weight: bold;">${data.customer_name}</span>
                        </div>
                        ${data.ref_or ? `
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span>For Invoice:</span>
                            <span>${data.ref_or}</span>
                        </div>
                        ` : ''}
                    </div>

                    <div style="border: 1px solid #000; padding: 15px; margin-bottom: 20px; text-align: center;">
                        <div style="font-size: 0.9rem; text-transform: uppercase;">Amount Paid</div>
                        <div style="font-size: 1.8rem; font-weight: bold; margin: 10px 0;">₱${formatCurrency(data.amount)}</div>
                        <div style="font-size: 0.9rem; font-style: italic;">(${data.method})</div>
                    </div>

                    ${(data.method !== 'Cash') ? `
                    <div style="margin-bottom: 20px; font-size: 0.9rem; padding: 10px; background: #f9f9f9;">
                        ${data.bank_name ? `<div><strong>Bank:</strong> ${data.bank_name}</div>` : ''}
                        ${data.check_number ? `<div><strong>Check #:</strong> ${data.check_number}</div>` : ''}
                        ${data.reference_number ? `<div><strong>Ref #:</strong> ${data.reference_number}</div>` : ''}
                    </div>
                    ` : ''}

                    <div style="margin-top: 40px; display: flex; justify-content: space-between; align-items: flex-end;">
                        <div style="text-align: center;">
                            <div style="border-bottom: 1px solid #000; width: 150px; margin-bottom: 5px;"></div>
                            <div style="font-size: 0.75rem;">Authorized Representative</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="border-bottom: 1px solid #000; width: 150px; margin-bottom: 5px;"></div>
                            <div style="font-size: 0.75rem;">Customer Signature</div>
                        </div>
                    </div>

                    <div style="margin-top: 30px; text-align: center; font-size: 0.8rem; color: #666;">
                        <p>This serves as an official proof of payment.</p>
                        <p>Thank you!</p>
                    </div>
                </div>
            `;
            $('#receiptContent').html(html);
        }

        $('#btnPrintReceipt').on('click', function () {
            const content = $('#receiptContent').html();
            const printWindow = window.open('', '_blank');
            printWindow.document.write('<html><head><title>Print Receipt</title>');
            printWindow.document.write('<style>body { font-family: "Courier New", Courier, monospace; padding: 20px; } @media print { body { padding: 0; } .no-print { display: none; } }</style>');
            printWindow.document.write('</head><body>');
            printWindow.document.write(content);
            printWindow.document.write('<script>window.onload = function() { window.print(); setTimeout(function() { window.close(); }, 500); };</script>');
            printWindow.document.write('</body></html>');
            printWindow.document.close();
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
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('paymentsAgingModal')).hide();
                }
            } else {
                return;
            }

            bootstrap.Modal.getOrCreateInstance(document.getElementById('recordPaymentModal')).show();
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
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('recordPaymentModal')).hide();
                    showSuccessModal('Payment recorded successfully.');

                    // Prepare receipt data
                    const paymentData = {
                        or_number: $('#payment_receipt_no').val(),
                        customer_name: $('#payment_customer_search').val(),
                        date: $('#payment_date').val(),
                        amount: $('#payment_amount').val(),
                        method: $('#payment_method').val(),
                        check_number: $('#payment_check_number').val(),
                        bank_name: $('#payment_bank_name').val(),
                        reference_number: $('#payment_reference_number').val(),
                        ref_or: $('#payment_or_number').val() // The SI number they paid for
                    };

                    renderPaymentReceipt(paymentData);
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('receiptModal')).show();

                    // Reset fields
                    $('#payment_details_container').addClass('d-none');
                    $('.payment-detail-field').addClass('d-none').find('input').val('');

                    loadSales();
                    loadPayments();
                    if (typeof loadAging === 'function') loadAging();
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

            bootstrap.Modal.getOrCreateInstance(document.getElementById('editPaymentModal')).show();
        });

        $('#editPaymentForm').on('submit', function (e) {
            e.preventDefault();
            const btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');

            $.post('../api/spareparts_inventory.php?action=edit_payment', $(this).serialize(), response => {
                if (response.success) {
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('editPaymentModal')).hide();
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

                // Only showing badge here as dashboard modal handles the alert
                if (!hasShownAlert) {
                     hasShownAlert = true; // Still mark as alert shown so we don't repeat badge logging
                }
            } else {
                $('#incoming-badge').addClass('d-none');
            }
        }, 'json');
    }

    // Modal populate logic for Incoming Transfers
    function loadIncomingTransferDetails() {
        console.log("Loading incoming transfer details...");
        $('#incomingTransferDetailsBody').html('<tr><td colspan="8" class="text-center py-4"><div class="spinner-border text-primary"></div></td></tr>');

        $.get('../api/spareparts_inventory.php?action=get_incoming_transfers_detailed', response => {
            console.log("Incoming details response:", response);
            if (response.success) {
                renderIncomingTransferDetails(response.data);
            } else {
                console.error("Failed to load incoming details:", response.message);
                $('#incomingTransferDetailsBody').html(`<tr><td colspan="8" class="text-center text-danger py-4">${response.message}</td></tr>`);
            }
        }).fail(xhr => {
            console.error("API Error loading incoming details:", xhr.responseText);
            $('#incomingTransferDetailsBody').html('<tr><td colspan="8" class="text-center text-danger py-4">Error connecting to server.</td></tr>');
        });
    }

    function renderIncomingTransferDetails(data) {
        let html = '';
        if (!data || data.length === 0) {
            html = '<tr><td colspan="8" class="text-center py-4 text-muted">No pending transfers found.</td></tr>';
            $('#incomingSelectionCount').addClass('d-none');
            $('#batchAcceptIncomingBtn, #batchRejectIncomingBtn').addClass('d-none');
            $('#selectAllIncoming').prop('checked', false).prop('disabled', true);
        } else {
            $('#selectAllIncoming').prop('disabled', false).prop('checked', false);
            $('#incomingSelectionCount').addClass('d-none');
            $('#batchAcceptIncomingBtn, #batchRejectIncomingBtn').addClass('d-none');
            $('#incomingSelectedNum').text('0');

            data.forEach(t => {
                html += `
                    <tr>
                        <td class="text-center">
                            <input class="form-check-input incoming-checkbox" type="checkbox" value="${t.id}" data-id="${t.id}">
                        </td>
                        <td><div class="fw-bold text-dark-green">${t.part_no}</div></td>
                        <td>${t.description}</td>
                        <td>${t.brand}</td>
                        <td class="text-center fw-bold">${t.qty}</td>
                        <td class="text-center">${t.transfer_date}</td>
                        <td class="text-center"><span class="badge bg-dark-green-light text-dark-green border border-dark-green">${t.from_branch}</span></td>
                        <td class="text-center">
                            <span class="badge bg-secondary text-white">Pending</span>
                        </td>
                    </tr>
                `;
            });
        }
        $('#incomingTransferDetailsBody').html(html);
        attachIncomingCheckboxListeners();
    }

    function attachIncomingCheckboxListeners() {
        $('#selectAllIncoming').off('change').on('change', function () {
            const isChecked = $(this).is(':checked');
            $('.incoming-checkbox').prop('checked', isChecked);
            updateIncomingSelection();
        });

        $('.incoming-checkbox').off('change').on('change', function () {
            const totalCheckboxes = $('.incoming-checkbox').length;
            const checkedCheckboxes = $('.incoming-checkbox:checked').length;
            $('#selectAllIncoming').prop('checked', totalCheckboxes === checkedCheckboxes && totalCheckboxes > 0);
            updateIncomingSelection();
        });

        $('#batchAcceptIncomingBtn').off('click').on('click', function () {
            submitBatchIncoming('accept');
        });

        $('#batchRejectIncomingBtn').off('click').on('click', function () {
            submitBatchIncoming('reject');
        });
    }

    function updateIncomingSelection() {
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

    function submitBatchIncoming(action) {
        const selectedIds = [];
        $('.incoming-checkbox:checked').each(function () {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) return;

        const actionText = action === 'accept' ? 'accept' : 'reject';

        Swal.fire({
            title: `Are you sure you want to ${actionText} ${selectedIds.length} selected transfer(s)?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: action === 'accept' ? '#26a69a' : '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: `Yes, ${actionText}!`
        }).then((result) => {
            if (result.isConfirmed) {
                executeBatchIncoming(selectedIds, action);
            }
        });
    }

    function executeBatchIncoming(ids, action) {
        let endpoint = action === 'accept' ? 'batch_receive_transfers' : 'batch_reject_transfers';

        $.post(`../api/spareparts_inventory.php?action=${endpoint}`, { transfer_ids: ids }, response => {
            if (response.success) {
                Swal.fire('Success', response.message || `Successfully processed transfers.`, 'success');
                loadIncomingTransferDetails(); // Refresh list inside modal
                if (typeof loadIncomingTransfersTable === 'function') {
                    loadIncomingTransfersTable(); // Refresh the table behind modal if exists
                }
                checkIncomingTransfers(); // Update red alert badge
            } else {
                Swal.fire('Error', response.message || 'Action failed.', 'error');
            }
        }, 'json').fail(xhr => {
            console.error("Batch processing error:", xhr.responseText);
            Swal.fire('Error', 'Server processing error.', 'error');
        });
    }

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
        bootstrap.Modal.getOrCreateInstance(document.getElementById('incomingTransferAlertModal')).hide();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('viewIncomingTransferModal')).show();
        loadIncomingTransferDetails();
    });

    // Also trigger modal when clicking the transfer-in tab/button if applicable
    $('#transfer-in-tab, #incoming-transfer-tab').on('click', function (e) {
        // If it's a link or button that should show the modal
        if ($(this).attr('id') === 'transfer-in-tab' || $(this).attr('id') === 'incoming-transfer-tab') {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('viewIncomingTransferModal')).show();
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
        bootstrap.Modal.getOrCreateInstance(document.getElementById('inventoryPreviewModal')).show();

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
                    config.headers = ['Date', 'Ref #', 'Transfer No', 'From', 'To', 'Part No', 'Qty', 'Status'];
                    config.keys = ['transfer_date', 'transfer_number', 'transfer_no', 'from_branch', 'to_branch', 'part_no', 'quantity', 'status'];
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
                case 'RETURN':
                    eventText = 'Returned (CM)';
                    fromText = escapeHtml(h.customer_name || 'Customer');
                    toText = escapeHtml(h.from_location || '-'); // usually returned back to branch
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

    // Expose globally for other scripts (e.g., sales_spareparts.js)
    window.printIndividualAging = printIndividualAging;

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

    // ——— INVENTORY PAGINATION —————————————————————————————————————————————————
    function loadInventory() {
        loadApiData('get_inventory_list', data => {
            inventoryData = data;
            inventoryFilteredData = data;
            inventoryCurrentPage = 1;
            renderInventory();
        }, 'inventoryTableBody');
    }

    function applyInventorySearch(query) {
        const q = query.trim().toLowerCase();
        if (!q) {
            inventoryFilteredData = inventoryData;
        } else {
            inventoryFilteredData = inventoryData.filter(item =>
                (item.part_no || '').toLowerCase().includes(q) ||
                (item.description || '').toLowerCase().includes(q) ||
                (item.brand || '').toLowerCase().includes(q) ||
                (item.bin_location || '').toLowerCase().includes(q)
            );
        }
        inventoryCurrentPage = 1;
        renderInventory();
    }

    // Wire up the search input (live, debounced)
    let inventorySearchTimer;
    $(document).on('input', '#inventorySearch', function () {
        clearTimeout(inventorySearchTimer);
        inventorySearchTimer = setTimeout(() => applyInventorySearch($(this).val()), 280);
    });

    function renderInventory() {
        const tbody = $('#inventoryTableBody');
        tbody.empty();

        const data = inventoryFilteredData;

        if (!data || data.length === 0) {
            tbody.html('<tr><td colspan="8" class="text-center text-muted py-5"><i class="bi bi-inbox fs-2 d-block mb-2"></i>No inventory items found.</td></tr>');
            if ($('#inventoryPageInfo').length) renderPagination('inventoryPagination', 'inventoryPageInfo', 0, 1, () => { });
            return;
        }

        const totalItems = data.length;
        const totalPages = Math.ceil(totalItems / PAGE_SIZE);
        if (inventoryCurrentPage > totalPages) inventoryCurrentPage = totalPages;

        const startIdx = (inventoryCurrentPage - 1) * PAGE_SIZE;
        const endIdx = Math.min(startIdx + PAGE_SIZE, totalItems);
        const pageData = data.slice(startIdx, endIdx);

        // Render rows
        pageData.forEach(item => {
            const stockLevel = Number(item.current_stock);
            const minStock = Number(item.min_stock || 0);
            const isLowStock = stockLevel <= minStock;
            const stockClass = isLowStock ? 'text-danger fw-bold' : 'fw-bold text-dark';
            const statusBadge = isLowStock
                ? '<span class="badge rounded-pill bg-danger">Low Stock</span>'
                : '<span class="badge rounded-pill bg-primary">In Stock</span>';
            const totalValue = formatCurrency(stockLevel * Number(item.cost || 0));
            const isBranchPage = window.isBranchPage === true;

            const branchCol = !isBranchPage
                ? '<td class="text-center py-3"><span class="badge bg-light text-dark border fw-semibold px-2 py-1" style="font-size:0.75rem;"><i class="bi bi-building me-1 text-primary" style="font-size:0.7rem;"></i>' + escapeHtml(item.current_branch || 'N/A') + '</span></td>'
                : '<td class="text-center py-3"><span class="badge bg-light text-dark border fw-semibold px-2 py-1" style="font-size:0.75rem;"><i class="bi bi-geo-alt-fill me-1 text-primary" style="font-size:0.7rem;"></i>' + escapeHtml(item.bin_location || 'N/A') + '</span></td>';

            const totalValueCol = !isBranchPage
                ? '<td class="text-end py-3 fw-bold text-success" style="font-size:0.875rem;">' + totalValue + '</td>'
                : '';

            const deleteBtn = canDelete
                ? '<button class="btn btn-sm delete-part-btn border-start" data-id="' + item.id + '" title="Delete" style="background:#fff;"><i class="bi bi-trash text-danger"></i></button>'
                : '';

            let rowHtml = `
            <tr class="align-middle border-bottom">
                <td class="ps-4 py-3">
                    <div class="fw-bold text-dark" style="font-size:0.875rem;">${escapeHtml(item.part_no)}</div>
                    <div class="small text-muted">${escapeHtml(item.brand)}</div>
                </td>
                <td class="py-3" style="max-width: 260px;">
                    <div class="small text-dark" style="line-height:1.4;">${escapeHtml(item.description)}</div>
                </td>
                ${branchCol}
                <td class="text-center py-3">
                    <span class="${stockClass}" style="font-size:1rem;">${stockLevel}</span>
                </td>
                <td class="text-end py-3 text-muted" style="font-size:0.875rem;">${formatCurrency(item.cost)}</td>
                <td class="text-end py-3 fw-bold text-primary" style="font-size:0.875rem;">${formatCurrency(item.price)}</td>
                ${totalValueCol}
                <td class="text-center py-3">${statusBadge}</td>
                <td class="text-center pe-4 py-3">
                    <div class="btn-group border rounded overflow-hidden" style="box-shadow:0 1px 4px rgba(0,0,0,0.08);">
                        <button class="btn btn-sm show-stock-card-btn" data-part-no="${item.part_no}" data-branch="${item.current_branch}" title="Stock Card" style="background:#fff;">
                            <i class="bi bi-card-list text-primary"></i>
                        </button>
                        <button class="btn btn-sm edit-part-btn border-start" data-id="${item.id}" title="Edit Part" style="background:#fff;">
                            <i class="bi bi-pencil text-secondary"></i>
                        </button>
                        ${deleteBtn}
                    </div>
                </td>
            </tr>`;
            tbody.append(rowHtml);
        });

        if ($('#inventoryPageInfo').length) {
            renderPagination('inventoryPagination', 'inventoryPageInfo', totalItems, inventoryCurrentPage, (pg) => {
                inventoryCurrentPage = pg;
                renderInventory();
                document.getElementById('inventoryTable')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        }
    }

    // ——————————————————————————————————————————————————————————————————————————
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€


    window.loadSales = function () {
        salesCurrentPage = 1; // Always reset to page 1 on load
        const branch = $('#salesFilterBranch').val() || 'all';
        const dateFrom = $('#salesFilterDateFrom').val() || '';
        const dateTo = $('#salesFilterDateTo').val() || '';

        const filterParams = {
            branch: branch,
            date_from: dateFrom,
            date_to: dateTo
        };

        loadApiData('get_sales_list', data => {
            salesData = data;
            renderSales(data);
        }, 'salesTableBody', filterParams);
    }

    let salesCurrentTypeFilter = 'all';

    function renderSales(data, typeFilter = 'all') {
        salesData = data;
        salesCurrentTypeFilter = typeFilter;
        const tbody = $('#salesTableBody');
        tbody.empty();

        let filtered = data;
        if (typeFilter !== 'all') {
            filtered = data.filter(s => s.transaction_type && s.transaction_type.toLowerCase() === typeFilter.toLowerCase());
        }
        salesFilteredData = filtered;

        if (!filtered || filtered.length === 0) {
            tbody.html('<tr><td colspan="10" class="text-center text-muted py-4">No sales records found.</td></tr>');
            if ($('#salesPageInfo').length) renderPagination('salesPagination', 'salesPageInfo', 0, 1, () => { });
            return;
        }

        const page = salesCurrentPage;
        const paged = filtered.slice((page - 1) * PAGE_SIZE, page * PAGE_SIZE);

        paged.forEach(s => {
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
                <td><div class="small text-muted">${escapeHtml(s.or_number)}</div></td>
                <td><div class="small text-muted">${escapeHtml(s.sales_force || 'N/A')}</div></td>
                <td class="text-end fw-bold">${formatCurrency(s.total_amount)}</td>
                <td class="text-center"><span class="badge ${typeBadge}">${escapeHtml(s.transaction_type.toUpperCase())}</span></td>
                <td class="text-end ${balanceVal > 0 ? 'text-danger' : ''}">${balanceText}</td>
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

        if ($('#salesPageInfo').length) {
            renderPagination('salesPagination', 'salesPageInfo', filtered.length, page, (pg) => {
                salesCurrentPage = pg;
                renderSales(salesData, salesCurrentTypeFilter);
                document.getElementById('salesTable')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        }
    }

    window.loadPayments = function () {
        paymentsCurrentPage = 1; // Always reset to page 1 on load to show newest
        loadApiData('get_payments_list', data => {
            paymentsData = data;
            renderPayments(data);
        }, 'paymentsTableBody');
    }

    function renderPayments(data) {
        const tbody = $('#paymentsTableBody');
        tbody.empty();
        paymentsAllData = data || [];

        if (!data || data.length === 0) {
            tbody.html('<tr><td colspan="6" class="text-center text-muted py-4">No payment records found.</td></tr>');
            if ($('#paymentsPageInfo').length) renderPagination('paymentsPagination', 'paymentsPageInfo', 0, 1, () => { });
            return;
        }

        const page = paymentsCurrentPage;
        const paged = data.slice((page - 1) * PAGE_SIZE, page * PAGE_SIZE);

        paged.forEach(p => {
            tbody.append(`
            <tr class="align-middle">
                <td><div class="fw-bold">${p.transaction_date}</div></td>
                ${!isBranchPage ? `<td><span class="badge bg-secondary">${escapeHtml(p.from_location)}</span></td>` : ''}
                <td><div class="fw-bold">${escapeHtml(p.customer_name)}</div></td>
                <td class="text-end fw-bold text-success">&#8369;${formatCurrency(p.amount)}</td>
                <td class="text-center"><span class="badge bg-light text-dark border">${escapeHtml(p.or_number)}</span></td>
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

        if ($('#paymentsPageInfo').length) {
            renderPagination('paymentsPagination', 'paymentsPageInfo', data.length, page, (pg) => {
                paymentsCurrentPage = pg;
                renderPayments(paymentsAllData);
                document.getElementById('paymentsTable')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        }
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
                        let typeIcon = '<i class="bi bi-cash-coin me-2 text-success"></i>';
                        if (l.type === 'OUT') typeIcon = '<i class="bi bi-cart me-2 text-primary"></i>';
                        else if (l.type === 'RETURN') typeIcon = '<i class="bi bi-arrow-return-left me-2 text-danger"></i>';
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
        transfersCurrentPage = 1;
        loadApiData('get_transfers_list', data => {
            transfersData = data;
            renderTransfers(data);
        }, 'transfersTableBody');
    }

    function renderTransfers(transfers) {
        transfersAllData = transfers || [];
        let html = '';
        const showFromBranch = $('#transfersTable thead th').length >= 6;
        const colCount = $('#transfersTable thead th').length || 6;

        if (!transfers || transfers.length === 0) {
            $('#transfersTableBody').html(`<tr><td colspan="${colCount}" class="text-center text-muted py-4">No outgoing transfers found.</td></tr>`);
            if ($('#transfersPageInfo').length) renderPagination('transfersPagination', 'transfersPageInfo', 0, 1, () => { });
            return;
        }

        const page = transfersCurrentPage;
        const paged = transfers.slice((page - 1) * PAGE_SIZE, page * PAGE_SIZE);

        paged.forEach(t => {
            let statusBadge = '';
            switch (t.status) {
                case 'Completed': statusBadge = '<span class="badge bg-dark-green text-white">Completed</span>'; break;
                case 'In-Transit': statusBadge = '<span class="badge bg-warning text-dark">In-Transit</span>'; break;
                case 'Rejected': statusBadge = '<span class="badge bg-danger text-white">Rejected</span>'; break;
                default: statusBadge = `<span class="badge bg-secondary text-white">${t.status}</span>`;
            }

            html += `
                <tr class="align-middle">
                    <td>
                        <div class="fw-bold">${t.transfer_date}</div>
                        ${t.transfer_no ? `<div class="small text-primary fw-bold" style="font-size:0.75rem">${t.transfer_no}</div>` : ''}
                    </td>
                    <td class="text-center"><span class="badge bg-light text-dark border px-3">${t.item_count} Items</span></td>
                    ${showFromBranch ? `<td><span class="badge bg-light text-dark-green border border-dark-green">${escapeHtml(t.from_branch)}</span></td>` : ''}
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
                    </td>
                </tr>
            `;
        });
        $('#transfersTableBody').html(html);

        if ($('#transfersPageInfo').length) {
            renderPagination('transfersPagination', 'transfersPageInfo', transfers.length, page, (pg) => {
                transfersCurrentPage = pg;
                renderTransfers(transfersAllData);
                document.getElementById('transfersTable')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        }

        // Listeners for view-transfer-btn are handled via the global delegated listener at line 935
    }

    function loadIncomingTransfers() {
        incomingTransfersCurrentPage = 1;
        loadApiData('get_incoming_transfers', data => {
            renderIncomingTransfers(data);
        }, 'incomingTransfersTableBody');
    }

    function renderIncomingTransfers(transfers) {
        incomingTransfersAllData = transfers || [];
        let html = '';

        if (!transfers || transfers.length === 0) {
            $('#incomingTransfersTableBody').html('<tr><td colspan="5" class="text-center py-4 text-muted">No pending transfers found.</td></tr>');
            if ($('#incomingTransfersPageInfo').length) renderPagination('incomingTransfersPagination', 'incomingTransfersPageInfo', 0, 1, () => { });
            return;
        }

        const page = incomingTransfersCurrentPage;
        const paged = transfers.slice((page - 1) * PAGE_SIZE, page * PAGE_SIZE);

        paged.forEach(t => {
            let statusBadge = '';
            switch (t.status) {
                case 'Completed': statusBadge = '<span class="badge bg-success text-white">Completed</span>'; break;
                case 'In-Transit': statusBadge = '<span class="badge bg-warning text-dark">In-Transit</span>'; break;
                case 'Rejected': statusBadge = '<span class="badge bg-danger text-white">Rejected</span>'; break;
                default: statusBadge = `<span class="badge bg-secondary text-white">${t.status}</span>`;
            }

            html += `
                <tr class="align-middle">
                    <td>
                        <div class="fw-bold">${t.transfer_date}</div>
                        ${t.transfer_no ? `<div class="small text-primary fw-bold" style="font-size:0.75rem">${t.transfer_no}</div>` : ''}
                    </td>
                    <td class="text-center"><span class="badge bg-light text-dark border px-3">${t.item_count || 0} Items</span></td>
                    <td><span class="badge bg-light text-success border border-success">${escapeHtml(t.from_branch)}</span></td>
                    <td class="text-center">${statusBadge}</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-primary view-transfer-btn" data-id="${t.id}">View</button>
                    </td>
                </tr>
            `;
        });
        $('#incomingTransfersTableBody').html(html);

        if ($('#incomingTransfersPageInfo').length) {
            renderPagination('incomingTransfersPagination', 'incomingTransfersPageInfo', transfers.length, page, (pg) => {
                incomingTransfersCurrentPage = pg;
                renderIncomingTransfers(incomingTransfersAllData);
                document.getElementById('incomingTransfersTable')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        }
    }

    function loadGlobalTransfers() {
        loadApiData('get_global_transfers', data => {
            globalTransfersData = data;
            renderGlobalTransfers(data);
        });
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
                        <div>
                            <span class="fw-bold text-dark-green">REF #${t.id}</span>
                            ${t.transfer_no ? `<span class="ms-2 badge bg-primary small fw-normal" style="font-size: 0.65rem;">${t.transfer_no}</span>` : ''}
                        </div>
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
                        <div class="small fw-bold text-uppercase text-muted" style="font-size: 0.7rem;">Items : <span class="badge bg-light text-dark border">${t.item_count || 0}</span></div>
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

        // Listener for view-global-transfer-btn is already handled by the global delegated listener at line 903
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

    // Global Transfers search button
    $('#globalTransfersSearchBtn').on('click', function () {
        $('#globalTransfersSearch').trigger('keyup');
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
            tbody.html('<tr><td colspan="5" class="text-center text-muted py-4">No spareparts activity logs found.</td></tr>');
            return;
        }

        data.forEach(log => {
            let actionBadge = '';
            const actionType = (log.action_type || '').toLowerCase();
            switch (actionType) {
                case 'insert': actionBadge = '<span class="badge bg-success">CREATE</span>'; break;
                case 'update': actionBadge = '<span class="badge bg-primary">UPDATE</span>'; break;
                case 'delete': actionBadge = '<span class="badge bg-danger">DELETE</span>'; break;
                default: actionBadge = `<span class="badge bg-secondary">${escapeHtml(log.action_type || 'â€“')}</span>`;
            }

            // Handle both action_timestamp and created_at column names
            const ts = log.action_timestamp || log.created_at || '';
            const table = (log.table_name || '').replace('spareparts_', '');

            tbody.append(`
            <tr class="align-middle">
                <td><div class="small fw-bold">${ts ? formatDateTime(ts) : 'â€“'}</div></td>
                <td><span class="badge bg-light text-dark border">${escapeHtml(log.username || 'â€“')}</span></td>
                <td class="text-center">${actionBadge}</td>
                <td><div class="small fw-bold text-muted text-uppercase">${escapeHtml(table)}</div></td>
                <td><div class="small">${escapeHtml(log.action_details || log.details || 'â€“')}</div></td>
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
            <div class="col-md-4">
                <div class="text-muted small text-uppercase fw-bold">Customer</div>
                <div class="fs-5 fw-bold">${escapeHtml(data.customer_name)}</div>
                <div class="text-muted small mt-1">SI #: <span class="fw-bold fs-6">${escapeHtml(data.or_number)}</span></div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small text-uppercase fw-bold">Sales Force</div>
                <div class="fs-5 fw-bold text-success"><i class="bi bi-person-badge me-2"></i>${escapeHtml(data.sales_force || 'N/A')}</div>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
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

    function applyRoleRestrictions() {
        if (typeof window.userRole === 'undefined') return;

        const role = window.userRole;
        const filter = window.filterType;

        // Role-based Tab Visibility
        if (role === 'Spareparts-Warehouse') {
            $('#sales-tab, #payments-tab, #dashboard-tab').hide();
            // Default to inventory if not specified
            if (!$('.nav-link.active').is(':visible')) {
                $('#inventory-tab').tab('show');
            }
        } else if (role === 'Spareparts-Retail') {
            // Restrict Sales to Cash Only
            if (filter === 'cash') {
                $('#charge-sales-tab, #all-sales-tab').hide();
                $('#cash-sales-tab').tab('show');
                // Hide components that might allow switching or seeing charge sales
                $('.col-balance').hide(); // Hide balance column
            }
        } else if (role === 'Spareparts-Admin' || role === 'Spareparts-Owner') {
            // These roles have full visibility in this module
            $('#inventory-tab, #sales-tab, #payments-tab, #dashboard-tab, #global-transfer-tab, #activity-log-tab').show();
        } else if (role === 'Spareparts-Sales') {
            // Sales role can see everything but maybe we want to hide certain admin things
        }

        // Handle specific landing tab from URL query parameter or hash
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');
        if (tabParam) {
            const tabEl = $(`#${tabParam}-tab`);
            if (tabEl.length) {
                tabEl.tab('show');
                // Ensure data loads if shown.bs.tab wasn't bound early enough
                setTimeout(() => { tabEl.trigger('shown.bs.tab'); }, 100);
            }
        } else {
            const hash = window.location.hash;
            if (hash) {
                $(`.nav-link[data-bs-target="${hash}"]`).tab('show');
            }
        }
    }

    // Initial Load
    applyRoleRestrictions();
    setupEventListeners();

    // Contextual Initial Load — determine which tab is active (server-side or default)
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    const activeTabOnLoad = tabParam || ($('.nav-link.active[data-bs-toggle="tab"]').attr('data-bs-target') || '#dashboard').replace('#', '');

    // Always load dashboard stats if the element exists (lightweight)
    if ($('#dashboard-stats-container').length || $('.stat-card').length) loadDashboardStats();

    // Load data for whichever tab is active on page load
    switch (activeTabOnLoad) {
        case 'inventory': if ($('#inventoryTableBody').length) loadInventory(); break;
        case 'sales': if ($('#salesTableBody').length) loadSales(); break;
        case 'payments': if ($('#paymentsTableBody').length) loadPayments(); break;
        case 'transfers':
        case 'sub-transfer-out':
        case 'transfer-out': if ($('#transfersTableBody').length) loadTransfers(); break;
        case 'transfers-in':
        case 'sub-transfer-in':
        case 'transfer-in': if ($('#incomingTransfersTableBody').length) loadIncomingTransfers(); break;
        case 'global-transfer': if ($('#global-transfer-tab').length || $('#global-transfer').length) loadGlobalTransfers(); break;
        case 'activityLog':
        case 'activity-log': if ($('#activityLogTableBody').length) loadActivityLog(); break;
        default: /* dashboard — stats already loaded above */ break;
    }

    // For non-admin/branch pages (older modules without the tabbed dashboard), load all relevant data upfront
    if (!$('#dashboard-tab').length && !tabParam && !$('.nav-tabs').length) {
        if ($('#inventoryTableBody').length) loadInventory();
        if ($('#transfersTableBody').length) loadTransfers();
        if ($('#incomingTransfersTableBody').length) loadIncomingTransfers();
        if ($('#salesTableBody').length) loadSales();
        if ($('#paymentsTableBody').length) loadPayments();
    }

    if ($('#returnsTableBody').length && typeof loadReturns === 'function') loadReturns();
    if ($('#pricelistsTableBody').length) typeof loadPricelistsTable === 'function' && loadPricelistsTable();
    // Removed redundant loadEmployeesTable call here as it's handled in sales_spareparts.js

    // Audit Log CSV export
    $(document).on('click', '#exportAuditLogBtn', function () {
        loadApiData('get_activity_log', function (data) {
            if (!data || data.length === 0) { showErrorModal('No audit log data to export.'); return; }
            const headers = ['Timestamp', 'Username', 'Action', 'Record ID', 'Details'];
            const rows = data.map(r => [
                `"${r.created_at || ''}"`,
                `"${r.username || ''}"`,
                `"${r.action || ''}"`,
                `"${r.record_id || ''}"`,
                `"${(r.details || '').replace(/"/g, '""')}"`
            ].join(','));
            const csv = [headers.join(','), ...rows].join('\n');
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `audit_log_${new Date().toISOString().slice(0, 10)}.csv`;
            a.click();
            URL.revokeObjectURL(url);
        });
    });

    // If filterType is cash, ensure sales are loaded as cash only
    if (window.filterType === 'cash') {
        setTimeout(() => {
            if ($('#cash-sales-tab').length) $('#cash-sales-tab').trigger('click');
        }, 500);
    }
});

// Global Utility Functions (Now using SweetAlert2)
window.showSuccessModal = function (message) {
    Swal.fire({
        title: 'Success',
        text: message,
        icon: 'success',
        confirmButtonText: 'OK'
    });
};

window.showErrorModal = function (message) {
    Swal.fire({
        title: 'Error',
        text: message,
        icon: 'error',
        confirmButtonText: 'OK'
    });
};

window.showConfirmModal = function (message, onConfirm) {
    Swal.fire({
        title: 'Are you sure?',
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#7367f0', // Premium Purple
        cancelButtonColor: '#ff4d4f',
        confirmButtonText: 'Yes, proceed',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            if (typeof onConfirm === 'function') onConfirm();
        }
    });
};
