/**
 * Extension script for Sales Dashboard (Customers and Returns/CM)
 */

$(document).ready(function() {

    // ===================== SHARED PAGINATION HELPER =====================
    const SALES_PAGE_SIZE = 20;
    function renderTablePagination(pagId, infoId, totalItems, currentPage, onChangePage) {
        const totalPages = Math.max(1, Math.ceil(totalItems / SALES_PAGE_SIZE));
        const pag  = document.getElementById(pagId);
        const info = document.getElementById(infoId);
        if (!pag || !info) return;
        const start = totalItems === 0 ? 0 : (currentPage - 1) * SALES_PAGE_SIZE + 1;
        const end   = Math.min(currentPage * SALES_PAGE_SIZE, totalItems);
        info.textContent = totalItems === 0 ? 'No records' : `Showing ${start}–${end} of ${totalItems} records`;
        pag.innerHTML = '';
        if (totalPages <= 1) return;
        const mkItem = (label, page, disabled, active) => {
            const li = document.createElement('li');
            li.className = 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '');
            const a = document.createElement('a');
            a.className = 'page-link rounded-pill px-3' + (active ? ' text-white' : '');
            a.href = '#';
            a.dataset.page = page;
            a.style.cssText = active ? 'background:var(--smdi-green,#004d40);border-color:var(--smdi-green,#004d40);' : 'border-color:#dee2e6;';
            a.innerHTML = label;
            li.appendChild(a);
            return li;
        };
        pag.appendChild(mkItem('<i class="bi bi-chevron-left" style="font-size:.7rem"></i>', currentPage - 1, currentPage === 1, false));
        const d = 2, rs = Math.max(1, currentPage - d), re = Math.min(totalPages, currentPage + d);
        if (rs > 1) {
            pag.appendChild(mkItem('1', 1, false, false));
            if (rs > 2) { const li = document.createElement('li'); li.className = 'page-item disabled'; li.innerHTML = '<span class="page-link px-2 border-0 bg-transparent">…</span>'; pag.appendChild(li); }
        }
        for (let p = rs; p <= re; p++) pag.appendChild(mkItem(p, p, false, p === currentPage));
        if (re < totalPages) {
            if (re < totalPages - 1) { const li = document.createElement('li'); li.className = 'page-item disabled'; li.innerHTML = '<span class="page-link px-2 border-0 bg-transparent">…</span>'; pag.appendChild(li); }
            pag.appendChild(mkItem(totalPages, totalPages, false, false));
        }
        pag.appendChild(mkItem('<i class="bi bi-chevron-right" style="font-size:.7rem"></i>', currentPage + 1, currentPage === totalPages, false));
        pag.addEventListener('click', function handler(e) {
            e.preventDefault();
            const a = e.target.closest('a[data-page]');
            if (!a) return;
            const pg = parseInt(a.dataset.page);
            if (!pg || pg < 1 || pg > totalPages) return;
            pag.removeEventListener('click', handler);
            onChangePage(pg);
        });
    }
    // ===================== END SHARED PAGINATION HELPER =====================
    // 1. Navbar Titles - Unified list
    const titles = {
        'customers': 'SALES - CUSTOMER MANAGEMENT',
        'sales': 'SALES - RECORD SALES',
        'payments': 'SALES - PAYMENTS',
        'returns': 'SALES - RETURNS / CM',
        'pricelists': 'SALES - PRICELIST MANAGEMENT',
        'employees': 'SALES - EMPLOYEE MANAGEMENT'
    };

    // 2. Attach Listeners First
    $('#mainTabs button').on('shown.bs.tab', function (e) {
        const target = $(e.target).data('bs-target').replace('#', '');
        if (titles[target]) {
            $('.navbar-brand').text(titles[target]);
        }
        
        if (target === 'employees') { loadEmployeesTable(); }
        if (target === 'pricelists') { loadPricelistsTable(); }
    });

    // 3. Handle Initial Tab (if any)
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    if (tabParam) {
        const $btn = $(`#mainTabs button[data-bs-target="#${tabParam}"]`);
        if ($btn.length) {
            $btn.tab('show');
            // Explicitly set title and load since 'shown.bs.tab' might not fire for initial show in some cases
            if (titles[tabParam]) $('.navbar-brand').text(titles[tabParam]);
            if (tabParam === 'employees') loadEmployeesTable();
            if (tabParam === 'pricelists') loadPricelistsTable();
        }
    }

    // 2. Customers Logic
    let customersData = [];

    // Helper functions for modals
    function showSuccess(msg) {
        $('#successMessage').text(msg);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('successModal')).show();
    }

    function showError(msg) {
        $('#errorMessage').text(msg);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('errorModal')).show();
    }

    // Temporary logic to render customers. We'll fetch from API soon.
    function renderCustomers(data) {
        const tbody = $('#customersTableBody');
        tbody.empty();
        if(!data || data.length === 0) {
            tbody.append('<tr><td colspan="5" class="text-center text-muted p-4">No customers found.</td></tr>');
            return;
        }

        data.forEach(cust => {
            const row = `
                <tr style="border-bottom: 1px solid #f0f0f0;">
                    <td class="py-3 ps-4 fw-bold" style="color: #2c3e50;">${cust.name}</td>
                    <td class="py-3" style="color: #6c757d;">${cust.contact_no || '-'}</td>
                    <td class="py-3" style="color: #6c757d;">${cust.address || '-'}</td>
                    <td class="py-3 text-end"><span class="badge-balance">₱${parseFloat(cust.balance || 0).toLocaleString(undefined, {minimumFractionDigits:2})}</span></td>
                    <td class="py-3 pe-4 text-center">
                        <button class="btn btn-sm btn-outline-primary btn-edit-customer" 
                            data-id="${cust.id}" 
                            data-name="${escapeHtml(cust.name)}" 
                            data-contact="${escapeHtml(cust.contact_no || '')}" 
                            data-address="${escapeHtml(cust.address || '')}"
                            data-rank="${cust.rank_level || 'Standard'}"
                            data-term="${cust.term || 0}"
                            data-limit="${cust.credit_limit || 0}"
                            title="Edit"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger btn-delete-customer" data-id="${cust.id}" title="Delete"><i class="bi bi-trash"></i></button>
                        <button class="btn btn-sm btn-outline-info btn-view-aging" data-name="${escapeHtml(cust.name)}" title="Aging View"><i class="bi bi-clock-history"></i></button>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    // Helper for escaping HTML attributes
    function escapeHtml(unsafe) {
        if (!unsafe) return '';
        return unsafe
             .toString()
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;");
    }

    // Call API
    function loadCustomers() {
        $.get('../api/sales_features_api.php?action=get_customers', function(res) {
            // Fallback parse if jQuery didn't treat it as JSON automatically
            if (typeof res === 'string') {
                try { res = JSON.parse(res); } catch(e) { console.error("Parse Error:", e); }
            }
            if(res && res.success) {
                customersData = res.data;
                renderCustomers(customersData);
            }
        }, 'json');
    }
    loadCustomers();

    // Manual triggers for Add Customer button
    $(document).on('click', '[data-bs-target="#addCustomerModal"]', function(e) {
        console.log("Add Customer button clicked");
        const modalEl = document.getElementById('addCustomerModal');
        if (modalEl) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        } else {
            console.error("Add Customer Modal element not found");
        }
    });

    $('#addCustomerForm').on('submit', function(e) {
        e.preventDefault();
        $.post('../api/sales_features_api.php?action=add_customer', $(this).serialize(), function(res) {
            if (typeof res === 'string') {
                try { res = JSON.parse(res); } catch(e) {}
            }
            if(res && res.success) {
                const modalEl = document.getElementById('addCustomerModal');
                bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                showSuccess('Customer added successfully!');
                $('#addCustomerForm')[0].reset();
                loadCustomers();
            } else {
                showError('Error: ' + (res ? res.message : 'Unknown response format'));
            }
        }, 'json').fail(function(xhr) {
            console.error("Add customer failed: ", xhr.responseText);
            showError("Error communicating with server.");
        });
    });

    $(document).on('click', '.btn-edit-customer', function() {
        $('#edit_cust_id').val($(this).data('id'));
        $('#edit_cust_name').val($(this).data('name'));
        $('#edit_cust_contact').val($(this).data('contact'));
        $('#edit_cust_address').val($(this).data('address'));
        $('#edit_cust_rank').val($(this).data('rank') || 'Standard');
        $('#edit_cust_term').val($(this).data('term') || 0);
        $('#edit_cust_limit').val($(this).data('limit') || 0);
        
        bootstrap.Modal.getOrCreateInstance(document.getElementById('editCustomerModal')).show();
    });

    $('#editCustomerForm').on('submit', function(e) {
        e.preventDefault();
        $.post('../api/sales_features_api.php?action=edit_customer', $(this).serialize(), function(res) {
            if(res.success) {
                const modalEl = document.getElementById('editCustomerModal');
                bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                showSuccess('Customer updated successfully!');
                loadCustomers();
            } else {
                showError('Error: ' + res.message);
            }
        }, 'json');
    });

    $(document).on('click', '.btn-delete-customer', function() {
        if (!confirm('Are you sure you want to delete this customer?')) return;
        
        const id = $(this).data('id');
        $.post('../api/sales_features_api.php?action=delete_customer', {id: id}, function(res) {
            if(res.success) {
                showSuccess('Customer deleted successfully!');
                loadCustomers();
            } else {
                showError('Error: ' + res.message);
            }
        }, 'json');
    });

    $(document).on('click', '.btn-view-aging', function() {
        const name = $(this).data('name');
        if (typeof window.printIndividualAging === 'function') {
            window.printIndividualAging(name, window.currentBranch);
        } else {
            showError("Aging logic not available.");
        }
    });

    // 3. Returns (CM) Logic
    let returnsData = [];

    let returnsCurrentPage = 1;
    let returnsAllData = [];

    function renderReturns(data) {
        const tbody = $('#returnsTableBody');
        tbody.empty();
        returnsAllData = data || [];

        if (!data || data.length === 0) {
            tbody.append('<tr><td colspan="8" class="text-center text-muted p-4"><i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>No returns recorded yet.</td></tr>');
            $('#returnsStat-count').text('0');
            $('#returnsStat-credited').text('₱0.00');
            $('#returnsStat-qty').text('0');
            renderTablePagination('returnsPagination', 'returnsPageInfo', 0, 1, () => {});
            return;
        }

        // Summary stats (always over full data)
        let totalCredited = 0, totalQty = 0;
        data.forEach(ret => {
            totalCredited += parseFloat(ret.amount_credited || 0);
            totalQty += parseInt(ret.qty_returned || 0);
        });
        $('#returnsStat-count').text(data.length);
        $('#returnsStat-credited').text('₱' + totalCredited.toLocaleString(undefined, {minimumFractionDigits:2}));
        $('#returnsStat-qty').text(totalQty);

        const page = returnsCurrentPage;
        const paged = data.slice((page - 1) * SALES_PAGE_SIZE, page * SALES_PAGE_SIZE);

        paged.forEach(ret => {
            totalCredited += parseFloat(ret.amount_credited || 0);
            totalQty += parseInt(ret.qty_returned || 0);
        });

        $('#returnsStat-count').text(data.length);
        $('#returnsStat-credited').text('₱' + totalCredited.toLocaleString(undefined, {minimumFractionDigits:2}));
        $('#returnsStat-qty').text(totalQty);

        data.forEach(ret => {
            // Format date safely
            const dateStr = ret.date_returned ? new Date(ret.date_returned + 'T00:00:00').toLocaleDateString('en-US', { year:'numeric', month:'short', day:'2-digit' }) : '-';
            const row = `
                <tr>
                    <td class="ps-3 small fw-bold">${dateStr}</td>
                    <td class="fw-bold">${ret.customer_name || '-'}</td>
                    <td><span class="badge bg-secondary">${ret.or_number || '-'}</span></td>
                    <td>
                        <div class="fw-bold small">${ret.part_no || '-'}</div>
                        <div class="small text-muted">${ret.part_name || ''}</div>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark border fw-bold px-2">${ret.qty_returned}</span>
                    </td>
                    <td class="text-end text-danger fw-bold">₱${parseFloat(ret.amount_credited).toLocaleString(undefined, {minimumFractionDigits:2})}</td>
                    <td class="small text-muted">${ret.remarks || ''}</td>
                    <td class="text-center">
                        <span class="badge bg-success-subtle text-success border border-success" style="font-size:0.7rem;">
                            <i class="bi bi-box-arrow-in-down me-1"></i>Returned to Inventory
                        </span>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });

        renderTablePagination('returnsPagination', 'returnsPageInfo', data.length, page, (pg) => {
            returnsCurrentPage = pg;
            renderReturns(returnsAllData);
            document.getElementById('returnsTable')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }
    function loadReturns() {
        $.get('../api/sales_features_api.php?action=get_returns', function(res) {
            if(typeof res === 'string') { try { res = JSON.parse(res); } catch(e) {} }
            if(res && res.success) {
                returnsData = res.data;
                renderReturns(returnsData);
            }
        }, 'json');
    }
    loadReturns();

    // Reload returns list when tab is clicked
    $(document).on('click', '#returns-tab', function() {
        loadReturns();
    });

    // Search/filter returns
    $('#returnsSearch').on('input', function() {
        const q = $(this).val().toLowerCase().trim();
        if (!q) {
            renderReturns(returnsData);
            return;
        }
        const filtered = returnsData.filter(r =>
            (r.customer_name || '').toLowerCase().includes(q) ||
            (r.or_number || '').toLowerCase().includes(q) ||
            (r.part_no || '').toLowerCase().includes(q) ||
            (r.part_name || '').toLowerCase().includes(q)
        );
        renderReturns(filtered);
    });


    // ===================== CM / RETURN LOGIC =====================
    let returnSalesData = [];

    // Set default return date
    $('#returnDate').val(new Date().toISOString().split('T')[0]);

    // Reset modal state when opened
    $('#recordReturnModal').on('show.bs.modal', function() {
        $('#returnCustomerSearch').val('');
        $('#returnCustomerName').val('');
        $('#returnCustomerResults').hide();
        $('#returnSalesContainer').addClass('d-none');
        $('#returnEmptyState').removeClass('d-none');
        $('#returnSalesBody').empty();
        $('#returnTotalCredit').text('₱0.00');
        $('#btnSubmitReturn').addClass('d-none');
        $('#selectAllReturnItems').prop('checked', false);
        $('#returnDate').val(new Date().toISOString().split('T')[0]);
        $('#returnRemarks').val('Returned to inventory');
        returnSalesData = [];
    });

    // Customer search typeahead
    let returnSearchTimer = null;
    $('#returnCustomerSearch').on('input', function() {
        const term = $(this).val().trim();
        const $results = $('#returnCustomerResults');
        
        clearTimeout(returnSearchTimer);
        if (term.length < 2) { $results.hide(); return; }

        returnSearchTimer = setTimeout(function() {
            $.get('../api/sales_features_api.php?action=search_customers_for_return', { term }, function(res) {
                $results.empty();
                if (res.success && res.data && res.data.length > 0) {
                    res.data.forEach(name => {
                        $results.append(`<button type="button" class="list-group-item list-group-item-action return-customer-pick fw-bold" data-name="${escapeHtml(name)}">
                            <i class="bi bi-person me-2 text-muted"></i>${escapeHtml(name)}
                        </button>`);
                    });
                    $results.show();
                } else {
                    $results.append('<div class="list-group-item text-muted small">No customers found</div>');
                    $results.show();
                }
            }, 'json');
        }, 300);
    });

    // Pick customer from dropdown
    $(document).on('click', '.return-customer-pick', function() {
        const name = $(this).data('name');
        $('#returnCustomerSearch').val(name);
        $('#returnCustomerName').val(name);
        $('#returnCustomerResults').hide();
        loadCustomerSalesForReturn(name);
    });

    // Load sales for selected customer
    function loadCustomerSalesForReturn(customerName) {
        $('#returnSalesBody').html('<tr><td colspan="8" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div> Loading...</td></tr>');
        $('#returnSalesContainer').removeClass('d-none');
        $('#returnEmptyState').addClass('d-none');
        $('#returnCustomerLabel').text(customerName);

        $.get('../api/sales_features_api.php?action=get_customer_sales', { customer_name: customerName }, function(res) {
            if (res.success && res.data.length > 0) {
                returnSalesData = res.data;
                renderReturnSalesItems(res.data);
            } else {
                returnSalesData = [];
                $('#returnSalesBody').html('<tr><td colspan="8" class="text-center text-muted py-4">No sales found for this customer.</td></tr>');
                $('#btnSubmitReturn').addClass('d-none');
            }
        }, 'json').fail(function() {
            $('#returnSalesBody').html('<tr><td colspan="8" class="text-center text-danger py-4">Error loading sales data.</td></tr>');
        });
    }

    function renderReturnSalesItems(data) {
        let html = '';
        let returnableCount = 0;
        data.forEach((item, idx) => {
            const isReturnable = item.returnable_qty > 0;
            if (isReturnable) returnableCount++;
            html += `
                <tr class="${!isReturnable ? 'text-muted opacity-50' : ''}">
                    <td>
                        <input type="checkbox" class="form-check-input return-item-check" data-index="${idx}" 
                            ${!isReturnable ? 'disabled' : ''}>
                    </td>
                    <td class="small">${item.transaction_date}</td>
                    <td><span class="badge bg-dark">${item.or_number}</span></td>
                    <td class="fw-bold">${item.part_no}</td>
                    <td class="small">${item.description || ''}</td>
                    <td class="text-center">${item.quantity} ${item.already_returned > 0 ? '<small class="text-warning">('+item.already_returned+' returned)</small>' : ''}</td>
                    <td class="text-end">₱${parseFloat(item.price).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    <td class="text-center">
                        ${isReturnable ? 
                            `<input type="number" class="form-control form-control-sm text-center return-qty-input" 
                                data-index="${idx}" min="1" max="${item.returnable_qty}" value="${item.returnable_qty}" 
                                style="width: 70px; margin: 0 auto;" disabled>` 
                            : '<span class="badge bg-success">All Returned</span>'
                        }
                    </td>
                </tr>
            `;
        });

        $('#returnSalesBody').html(html);
        $('#returnItemCount').text(returnableCount + ' returnable item(s)');
        updateReturnTotal();
    }

    // Select all checkbox
    $('#selectAllReturnItems').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('.return-item-check:not(:disabled)').prop('checked', isChecked);
        $('.return-qty-input').prop('disabled', function() {
            return !$(this).closest('tr').find('.return-item-check').is(':checked');
        });
        updateReturnTotal();
    });

    // Individual checkbox toggle
    $(document).on('change', '.return-item-check', function() {
        const idx = $(this).data('index');
        const isChecked = $(this).is(':checked');
        $(this).closest('tr').find('.return-qty-input').prop('disabled', !isChecked);
        
        const total = $('.return-item-check:not(:disabled)').length;
        const checked = $('.return-item-check:checked').length;
        $('#selectAllReturnItems').prop('checked', total === checked && total > 0);
        updateReturnTotal();
    });

    // Qty input change
    $(document).on('input', '.return-qty-input', function() {
        const max = parseInt($(this).attr('max'));
        let val = parseInt($(this).val());
        if (val > max) { $(this).val(max); val = max; }
        if (val < 1) { $(this).val(1); val = 1; }
        updateReturnTotal();
    });

    function updateReturnTotal() {
        let total = 0;
        let selectedCount = 0;
        $('.return-item-check:checked').each(function() {
            const idx = $(this).data('index');
            const item = returnSalesData[idx];
            const qty = parseInt($(this).closest('tr').find('.return-qty-input').val()) || 0;
            total += qty * parseFloat(item.price);
            selectedCount++;
        });

        $('#returnTotalCredit').text('₱' + total.toLocaleString(undefined, {minimumFractionDigits: 2}));
        
        if (selectedCount > 0) {
            $('#btnSubmitReturn').removeClass('d-none');
        } else {
            $('#btnSubmitReturn').addClass('d-none');
        }
    }

    // Submit return
    $('#btnSubmitReturn').on('click', function() {
        const selectedItems = [];
        $('.return-item-check:checked').each(function() {
            const idx = $(this).data('index');
            const item = returnSalesData[idx];
            const qty = parseInt($(this).closest('tr').find('.return-qty-input').val()) || 0;
            selectedItems.push({
                tx_id: item.id,
                qty: qty
            });
        });

        if (selectedItems.length === 0) {
            showError('Please select at least one item to return.');
            return;
        }

        const customerName = $('#returnCustomerName').val();
        const date = $('#returnDate').val();
        const remarks = $('#returnRemarks').val();

        if (!date) {
            showError('Please select a return date.');
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...');

        $.post('../api/sales_features_api.php?action=add_return', {
            items: JSON.stringify(selectedItems),
            customer_name: customerName,
            date: date,
            remarks: remarks
        }, function(res) {
            btn.prop('disabled', false).html('<i class="bi bi-check-circle me-2"></i>Process Return');
            if (res.success) {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('recordReturnModal')).hide();
                showSuccess(res.message);
                loadReturns();
            } else {
                showError(res.message);
            }
        }, 'json').fail(function() {
            btn.prop('disabled', false).html('<i class="bi bi-check-circle me-2"></i>Process Return');
            showError('Server error while processing return.');
        });
    });

    // Close dropdown on outside click
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#returnCustomerSearch, #returnCustomerResults').length) {
            $('#returnCustomerResults').hide();
        }
    });

    // ===================== MANAGE SALES FORCE MODAL =====================

    function loadSalesForceList() {
        $('#salesForceListContainer').html('<div class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm text-success me-2"></div> Loading...</div>');
        $.get('../api/spareparts_inventory.php?action=get_sales_force', function (res) {
            if (!res.success || !res.data || res.data.length === 0) {
                $('#salesForceListContainer').html('<div class="text-center text-muted py-4"><i class="bi bi-people fs-3 d-block mb-2 opacity-50"></i>No employees added yet.</div>');
                return;
            }
            let html = '<ul class="list-group list-group-flush">';
            res.data.forEach(emp => {
                const badgeClass = parseInt(emp.total_sales || 0) > 0 ? 'bg-success' : 'bg-secondary';
                html += `<li class="list-group-item d-flex justify-content-between align-items-center px-0">
                    <div class="d-flex flex-column">
                        <span class="fw-bold"><i class="bi bi-person-circle me-2 text-success"></i>${escapeHtml(emp.employee_name)}</span>
                        <small class="text-muted ms-4">Total Sales: <span class="badge ${badgeClass}">${emp.total_sales || 0}</span></small>
                    </div>
                    <button class="btn btn-sm btn-outline-danger rounded-pill sf-delete-btn" data-id="${emp.id}" title="Remove">
                        <i class="bi bi-trash"></i>
                    </button>
                </li>`;
            });
            html += '</ul>';
            $('#salesForceListContainer').html(html);
        }, 'json').fail(function () {
            $('#salesForceListContainer').html('<div class="alert alert-danger">Failed to load employees.</div>');
        });
    }

    // Load list when modal opens
    $('#manageSalesForceModal').on('show.bs.modal', function () {
        loadSalesForceList();
        $('#sf_employee_name').val('');
    });

    // Add employee
    $('#addSalesForceForm').on('submit', function (e) {
        e.preventDefault();
        const name = $('#sf_employee_name').val().trim();
        const pos = $('#sf_position').val().trim();
        if (!name) return;
        $.post('../api/spareparts_inventory.php?action=add_sales_force', { employee_name: name, position: pos }, function (res) {
            if (res.success) {
                $('#sf_employee_name').val('');
                $('#sf_position').val('');
                loadSalesForceList();
                loadEmployeesTable();
                if (typeof showSuccessModal === 'function') showSuccessModal('Employee Added Successfully');
            } else {
                if (typeof showErrorModal === 'function') showErrorModal(res.message);
                else alert(res.message);
            }
        }, 'json');
    });

    // Delete employee
    $(document).on('click', '.sf-delete-btn', function () {
        const id = $(this).data('id');
        if (!confirm('Remove this employee from Sales Force?')) return;
        $.post('../api/spareparts_inventory.php?action=delete_sales_force', { id }, function (res) {
            if (res.success) {
                loadSalesForceList();
                if ($('#employees').hasClass('active')) {
                    loadEmployeesTable();
                }
            } else {
                alert(res.message || 'Failed to delete.');
            }
        }, 'json');
    });

    // ===================== EMPLOYEES TAB LOGIC =====================
    
    let employeesData = [];

    let employeesCurrentPage = 1;
    let employeesAllData = [];

    function renderEmployeesTable(data) {
        const tbody = $('#employeesTableBody');
        tbody.empty();
        employeesAllData = data || [];
        if (!data || data.length === 0) {
            tbody.append('<tr><td colspan="4" class="text-center text-muted p-4"><i class="bi bi-people fs-2 d-block mb-2 opacity-50"></i>No employee records found for this branch.</td></tr>');
            renderTablePagination('employeesPagination', 'employeesPageInfo', 0, 1, () => {});
            return;
        }
        const page = employeesCurrentPage;
        const paged = data.slice((page - 1) * SALES_PAGE_SIZE, page * SALES_PAGE_SIZE);
        paged.forEach(emp => {
            const count = parseInt(emp.total_sales || 0);
            const row = `
                <tr>
                    <td>
                        <div class="fw-bold fs-6 text-dark">${escapeHtml(emp.employee_name)}</div>
                        <div class="small text-muted fw-bold text-uppercase" style="font-size: 0.7rem;">${escapeHtml(emp.position || 'No Position Set')}</div>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border-0 shadow-sm px-3 py-2 rounded-pill">
                            <i class="bi bi-building me-1"></i>${escapeHtml(emp.branch || 'N/A')}
                        </span>
                    </td>
                    <td>
                        ${count > 0 ? 
                            `<span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill cursor-pointer view-sf-sales" data-name="${escapeHtml(emp.employee_name)}" title="Click to view sales">
                                <i class="bi bi-cart-check-fill me-1"></i>${count} Sales
                            </span>` : 
                            '<span class="badge bg-light text-muted border px-3 py-2 rounded-pill">No sales yet</span>'}
                    </td>
                    <td class="text-end">
                        <div class="btn-group shadow-sm rounded-pill overflow-hidden">
                            <button class="btn btn-sm btn-white border-0 sf-edit-btn" data-id="${emp.id}" data-name="${escapeHtml(emp.employee_name)}" data-pos="${escapeHtml(emp.position || '')}" title="Edit Staff">
                                <i class="bi bi-pencil-square text-primary"></i>
                            </button>
                            <button class="btn btn-sm btn-white border-0 sf-delete-btn-tab" data-id="${emp.id}" title="Remove Employee">
                                <i class="bi bi-trash3 text-danger"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
        renderTablePagination('employeesPagination', 'employeesPageInfo', data.length, page, (pg) => {
            employeesCurrentPage = pg;
            renderEmployeesTable(employeesAllData);
            document.getElementById('employeesTable')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }
    window.renderEmployeesTable = renderEmployeesTable; // Make it global just in case

    function loadEmployeesTable() {
        $.get('../api/spareparts_inventory.php?action=get_sales_force', function(res) {
            if (res.success) {
                employeesData = res.data;
                renderEmployeesTable(employeesData);
            }
        }, 'json').fail(function() {
            $('#employeesTableBody').html('<tr><td colspan="4" class="text-center text-danger p-4">Error loading employees.</td></tr>');
        });
    }
    window.loadEmployeesTable = loadEmployeesTable; // Hoist to global

    // Search filter
    $('#employeesSearch').on('input', function() {
        const query = $(this).val().toLowerCase();
        const filtered = employeesData.filter(emp => 
            emp.employee_name.toLowerCase().includes(query)
        );
        renderEmployeesTable(filtered);
    });

    // Re-use sf-delete-btn logic but maybe with a slightly different class for the tab table?
    // Actually I can just add sf-delete-btn-tab to handle the same action and then reload the table.
    $(document).on('click', '.sf-delete-btn-tab', function () {
        const id = $(this).data('id');
        if (!confirm('Are you sure you want to remove this employee from your branch records?')) return;
        $.post('../api/spareparts_inventory.php?action=delete_sales_force', { id }, function (res) {
            if (res.success) {
                loadEmployeesTable();
                // Also load the modal list if it's open (it will reload next time it opens anyway)
            } else {
                alert(res.message || 'Failed to delete.');
            }
        }, 'json');
    });

    $(document).on('click', '.sf-edit-btn', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const pos = $(this).data('pos');
        $('#edit_sf_id').val(id);
        $('#edit_sf_name').val(name);
        $('#edit_sf_position').val(pos);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('editEmployeeModal')).show();
    });

    $('#editEmployeeForm').on('submit', function(e) {
        e.preventDefault();
        const id = $('#edit_sf_id').val();
        const name = $('#edit_sf_name').val();
        const position = $('#edit_sf_position').val();
        $.post('../api/spareparts_inventory.php?action=edit_sales_force', { id, employee_name: name, position }, function(res) {
            if (res.success) {
                bootstrap.Modal.getInstance(document.getElementById('editEmployeeModal')).hide();
                loadEmployeesTable();
                if (typeof showSuccessModal === 'function') showSuccessModal('Staff details updated.');
            } else {
                if (typeof showErrorModal === 'function') showErrorModal(res.message);
                else alert(res.message);
            }
        }, 'json');
    });

    $(document).on('click', '.view-sf-sales', function() {
        const name = $(this).data('name');
        // Switch to Sales tab and filter by this employee
        $('#all-sales-tab').trigger('click');
        $('#salesSearch').val(name).trigger('input');
        
        // Find the Sales tab button in mainTabs
        const salesTabBtn = document.querySelector('[data-bs-target="#sales"]');
        if (salesTabBtn) bootstrap.Tab.getOrCreateInstance(salesTabBtn).show();
    });

    // Check if initial tab is employees
    if (tabParam === 'employees') {
        loadEmployeesTable();
    }
    if (tabParam === 'pricelists') {
        loadPricelistsTable();
    }

    // ===================== PRICELISTS TAB LOGIC =====================
    let pricelistsData = [];

    let pricelistsCurrentPage = 1;
    let pricelistsAllData = [];

    function renderPricelistsTable(data) {
        const tbody = $('#pricelistsTableBody');
        tbody.empty();
        pricelistsAllData = data || [];
        if (!data || data.length === 0) {
            tbody.append('<tr><td colspan="5" class="text-center text-muted p-4">No rank-based prices found.</td></tr>');
            renderTablePagination('pricelistsPagination', 'pricelistsPageInfo', 0, 1, () => {});
            return;
        }
        const page = pricelistsCurrentPage;
        const paged = data.slice((page - 1) * SALES_PAGE_SIZE, page * SALES_PAGE_SIZE);
        paged.forEach(pl => {
            const row = `
                <tr>
                    <td class="ps-4">
                        <div class="fw-bold">${pl.brand} - ${pl.part_no}</div>
                        <div class="small text-muted">${pl.description}</div>
                    </td>
                    <td><span class="badge bg-navy px-3">${pl.rank_level}</span></td>
                    <td class="text-end fw-bold text-success fs-5">₱${parseFloat(pl.price).toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
                    <td class="text-center small text-muted">${new Date(pl.updated_at || pl.created_at).toLocaleString()}</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-danger btn-delete-pricelist rounded-pill px-3" data-id="${pl.id}">
                            <i class="bi bi-trash me-1"></i>Remove
                        </button>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
        renderTablePagination('pricelistsPagination', 'pricelistsPageInfo', data.length, page, (pg) => {
            pricelistsCurrentPage = pg;
            renderPricelistsTable(pricelistsAllData);
            document.getElementById('pricelistsTable')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }
    window.loadPricelistsTable = function() {
        const query = $('#pricelistsSearch').val();
        $.get('../api/spareparts_inventory.php?action=get_pricelists', { query }, function (res) {
            if (res.success) {
                pricelistsData = res.data;
                renderPricelistsTable(pricelistsData);
            }
        }, 'json');
    }

    $('#pricelistsSearch').on('input', loadPricelistsTable);

    $(document).on('click', '.btn-delete-pricelist', function () {
        const id = $(this).data('id');
        if (!confirm('Are you sure you want to remove this rank-specific price?')) return;
        $.post('../api/spareparts_inventory.php?action=delete_pricelist', { id }, function (res) {
            if (res.success) loadPricelistsTable();
            else alert(res.message);
        }, 'json');
    });

    // Save Pricelist Logic
    $('#savePricelistForm').on('submit', function (e) {
        e.preventDefault();
        const btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).text('Saving...');
        $.post('../api/spareparts_inventory.php?action=save_pricelist', $(this).serialize(), function (res) {
            btn.prop('disabled', false).text('Save Price');
            if (res.success) {
                $('#savePricelistModal').modal('hide');
                $('#savePricelistForm')[0].reset();
                $('#pl_selected_part_info').addClass('d-none');
                loadPricelistsTable();
                showSuccess('Pricelist saved successfully!');
            } else {
                showError(res.message);
            }
        }, 'json');
    });

    // Part Search for Pricelist
    $('#pl_part_search').on('input', function () {
        const term = $(this).val().trim();
        const $results = $('#pl_search_results');
        if (term.length < 2) { $results.hide(); return; }

        $.get('../api/spareparts_inventory.php?action=search_inventory_parts', { term }, function (res) {
            $results.empty();
            if (res.success && res.data && res.data.length > 0) {
                res.data.forEach(part => {
                    $results.append(`<button type="button" class="list-group-item list-group-item-action pl-part-pick" 
                        data-part_no="${part.part_no}" data-desc="${part.description}" data-price="${part.price}" data-brand="${part.brand || ''}">
                        <div class="fw-bold">${part.part_no}</div>
                        <div class="small">${part.brand ? part.brand + ' - ' : ''}${part.description}</div>
                    </button>`);
                });
                $results.show();
            } else {
                $results.hide();
            }
        }, 'json');
    });

    $(document).on('click', '.pl-part-pick', function () {
        const pno = $(this).data('part_no');
        const desc = $(this).data('desc');
        const price = $(this).data('price');
        const brand = $(this).data('brand');

        $('#pl_part_no').val(pno);
        $('#pl_part_search').val('');
        $('#pl_search_results').hide();
        
        $('#pl_sel_part_no').text(pno);
        $('#pl_sel_description').text((brand ? brand + ' - ' : '') + desc);
        $('#pl_sel_price').text('₱' + parseFloat(price).toLocaleString(undefined, { minimumFractionDigits: 2 }));
        $('#pl_selected_part_info').removeClass('d-none');
    });

    // ---- BULK PRICELIST LOGIC ----
    let bulkItems = [];

    $('#bulk_part_search').on('input', function () {
        const term = $(this).val().trim();
        const $results = $('#bulk_search_results');
        if (term.length < 2) { $results.hide(); return; }

        $.get('../api/spareparts_inventory.php?action=search_inventory_parts', { term }, function (res) {
            $results.empty();
            if (res.success && res.data && res.data.length > 0) {
                res.data.forEach(part => {
                    $results.append(`<button type="button" class="list-group-item list-group-item-action bulk-part-pick" 
                        data-part='${JSON.stringify(part).replace(/'/g, "&apos;")}'>
                        <div class="fw-bold fs-6">${part.part_no}</div>
                        <div class="small text-muted">${part.brand ? part.brand + ' - ' : ''}${part.description}</div>
                    </button>`);
                });
                $results.show();
            } else {
                $results.hide();
            }
        }, 'json');
    });

    $(document).on('click', '.bulk-part-pick', function () {
        const part = $(this).data('part');
        if (bulkItems.some(bi => bi.id === part.id)) {
            alert('This product is already in the list.');
            return;
        }

        bulkItems.push({
            id: part.id,
            part_no: part.part_no,
            description: part.description,
            brand: part.brand || '',
            price: part.price, // Default price
            rank_price: part.price // Suggested rank price initially same as default
        });

        $('#bulk_part_search').val('');
        $('#bulk_search_results').hide();
        renderBulkTable();
    });

    function renderBulkTable() {
        const tbody = $('#bulkPricelistItems');
        tbody.empty();

        if (bulkItems.length === 0) {
            tbody.append('<tr id="bulk-empty-row"><td colspan="4" class="text-center text-muted p-4">No products added yet. Use the search to add items.</td></tr>');
            $('#bulk-count').text(0);
            return;
        }

        bulkItems.forEach((item, index) => {
            tbody.append(`
                <tr>
                    <td class="ps-3 py-2">
                        <div class="fw-bold">${item.brand ? item.brand + ' - ' : ''}${item.part_no}</div>
                        <div class="small text-muted text-truncate" style="max-width: 300px;">${item.description}</div>
                    </td>
                    <td class="text-end text-muted small">₱${parseFloat(item.price).toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
                    <td class="text-end">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">₱</span>
                            <input type="number" step="0.01" class="form-control text-end fw-bold bulk-item-price-input" 
                                data-index="${index}" value="${parseFloat(item.rank_price).toFixed(2)}">
                        </div>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-link text-danger p-0 remove-bulk-item" data-index="${index}">
                            <i class="bi bi-x-circle-fill fs-5"></i>
                        </button>
                    </td>
                </tr>
            `);
        });

        $('#bulk-count').text(bulkItems.length);
    }

    $(document).on('input', '.bulk-item-price-input', function() {
        const index = $(this).data('index');
        bulkItems[index].rank_price = $(this).val();
    });

    $(document).on('click', '.remove-bulk-item', function() {
        const index = $(this).data('index');
        bulkItems.splice(index, 1);
        renderBulkTable();
    });

    $('#saveBulkPricelistBtn').on('click', function() {
        const rank = $('#bulk_rank_level').val().trim();
        if (!rank) { alert('Please select a rank level first!'); return; }
        if (bulkItems.length === 0) { alert('Please add at least one product.'); return; }

        const items = bulkItems.map(bi => ({
            part_no: bi.part_no,
            price: bi.rank_price
        }));

        const btn = $(this);
        const originalText = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');

        $.post('../api/spareparts_inventory.php?action=save_bulk_pricelists', { rank_level: rank, items: items }, function(res) {
            btn.prop('disabled', false).html(originalText);
            if (res.success) {
                $('#bulkPricelistModal').modal('hide');
                bulkItems = [];
                renderBulkTable();
                $('#bulk_rank_level').val('');
                loadPricelistsTable();
                showSuccess(res.message);
            } else {
                showError(res.message);
            }
        }, 'json').fail(() => {
            btn.prop('disabled', false).html(originalText);
            showError('Server error while saving bulk pricelist.');
        });
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('#pl_part_search, #pl_search_results, #bulk_part_search, #bulk_search_results').length) {
            $('#pl_search_results, #bulk_search_results').hide();
        }
    });

    // Auto-fill all date/month pickers with today's date
    function autoFillDates(container = document) {
        const today = new Date();
        const yyyy = today.getFullYear();
        let mm = today.getMonth() + 1;
        let dd = today.getDate();
        if (dd < 10) dd = '0' + dd;
        if (mm < 10) mm = '0' + mm;
        const formattedDate = `${yyyy}-${mm}-${dd}`;
        
        $(container).find('input[type="date"], input[type="datetime-local"]').each(function() {
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

    autoFillDates();
    $(document).on('shown.bs.modal', function (e) {
        autoFillDates(e.target);
    });
});
