/**
 * Inventory IN Modal Logic
 * Migrated from inventory_in.php
 */

let inCart = [];
let currentlyScannedItem = null;
let searchTimer = null;
let dropdownIndex = -1;
let dropdownResults = [];

function initInventoryIn() {
    const modalElement = document.getElementById('inventoryInModal');
    if (!modalElement) return;

    modalElement.addEventListener('shown.bs.modal', function () {
        document.getElementById('invoiceNo').focus();
    });

    // Move to part search when Enter pressed in header fields
    $('#invoiceNo, #supplier, #dateReceived').on('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); $('#partSearchInput').focus(); }
    });

    // ---- SEARCH INPUT BEHAVIOUR ----
    $('#partSearchInput').on('input', function () {
        let q = $(this).val().trim();
        clearTimeout(searchTimer);
        if (q.length < 1) { hideInDropdown(); return; }
        searchTimer = setTimeout(() => doInSearch(q), 220);
    });

    $('#partSearchInput').on('keydown', function (e) {
        if (e.key === 'ArrowDown') { e.preventDefault(); moveInDropdownSelection(1); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); moveInDropdownSelection(-1); }
        else if (e.key === 'Enter') {
            e.preventDefault();
            if (dropdownIndex >= 0 && dropdownResults[dropdownIndex]) {
                selectInPart(dropdownResults[dropdownIndex]);
            } else {
                let q = $(this).val().trim();
                if (q) commitInPartSearch(q);
            }
        } else if (e.key === 'Escape') {
            hideInDropdown();
        }
    });

    // Close dropdown on outside click
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.part-search-wrapper').length) hideInDropdown();
    });

    // ---- COST INPUT — compare to prev ----
    $('#scannedCost').on('input', function () {
        if (!currentlyScannedItem || currentlyScannedItem.isNew) return;
        let newCost = parseFloat($(this).val()) || 0;
        let prevCost = parseFloat(currentlyScannedItem.cost) || 0;
        let $input = $(this);
        $input.removeClass('cost-hike-input cost-lower-input');
        $('#costChangeIndicator').empty();
        $('#costUpdateHint').addClass('d-none');
        if (prevCost > 0 && newCost !== prevCost) {
            $('#costUpdateHint').removeClass('d-none');
            if (newCost > prevCost) {
                $input.addClass('cost-hike-input');
                let pct = ((newCost - prevCost) / prevCost * 100).toFixed(1);
                $('#costChangeIndicator').html(`<span class="text-danger small"><i class="bi bi-arrow-up-short"></i>+${pct}% cost hike</span>`);
            } else {
                $input.addClass('cost-lower-input');
                let pct = ((prevCost - newCost) / prevCost * 100).toFixed(1);
                $('#costChangeIndicator').html(`<span class="text-success small"><i class="bi bi-arrow-down-short"></i>-${pct}% lower cost</span>`);
            }
        }
    });

    $('#addScannedPart').on('click', addCurrentPartToInCart);

    $('#scannedQty').on('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); $('#scannedCost').focus().select(); }
    });
    $('#scannedCost').on('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); addCurrentPartToInCart(); }
    });
    $('#newPartDesc').on('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); $('#scannedQty').focus().select(); }
    });

    $('#submitStockIn').on('click', submitStockInAction);

    $('#clearCart').on('click', function () {
        if (inCart.length === 0) return;
        Swal.fire({
            title: 'Clear all items?', icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#d32f2f', confirmButtonText: 'Yes, clear'
        }).then(r => { if (r.isConfirmed) { inCart = []; renderInCart(); } });
    });

    $(document).on('click', '#costHistoryLink', function () {
        let pno = currentlyScannedItem ? currentlyScannedItem.part_no : '';
        if (pno) showInCostHistory(pno);
    });
}

function doInSearch(q) {
    $.get('../api/spareparts_inventory.php', { action: 'search_parts_for_in', query: q }, function (res) {
        if (res.success && res.data && res.data.length) {
            dropdownResults = res.data;
            showInDropdown(res.data, q);
        } else {
            dropdownResults = [];
            showInDropdownNew(q);
        }
    });
}

function showInDropdown(parts, q) {
    dropdownIndex = -1;
    let html = '';
    parts.forEach((p, i) => {
        let pno = highlightInMatch(p.part_no, q);
        let desc = highlightInMatch(p.description, q);
        html += `<div class="search-item" data-idx="${i}">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>${pno}</strong>
                    <span class="part-no-badge">${escInHtml(p.brand || '')}</span>
                    <div class="small text-muted">${desc}</div>
                </div>
                <div class="text-end">
                    <div class="fw-bold text-primary">₱${parseFloat(p.cost||0).toFixed(2)}</div>
                    <div class="small text-muted">Stock: ${p.current_stock}</div>
                </div>
            </div>
        </div>`;
    });
    $('#searchDropdown').html(html).show();

    $('#searchDropdown').off('click').on('click', '.search-item', function () {
        let idx = parseInt($(this).data('idx'));
        selectInPart(dropdownResults[idx]);
    });
}

function showInDropdownNew(q) {
    dropdownIndex = -1;
    let html = `<div class="search-item" id="addNewPartOption">
        <i class="bi bi-plus-circle-fill text-warning me-2"></i>
        <strong>No match found.</strong> Add <em>"${escInHtml(q)}"</em> as a <strong>new part</strong>?
    </div>`;
    $('#searchDropdown').html(html).show();
    $('#addNewPartOption').on('click', function () {
        commitInPartSearch(q);
    });
}

function hideInDropdown() {
    $('#searchDropdown').hide();
    dropdownIndex = -1;
    dropdownResults = [];
}

function moveInDropdownSelection(dir) {
    let items = $('#searchDropdown .search-item');
    if (!items.length) return;
    items.removeClass('active');
    dropdownIndex = Math.max(0, Math.min(items.length - 1, dropdownIndex + dir));
    $(items[dropdownIndex]).addClass('active');
}

function highlightInMatch(text, q) {
    if (!text) return '';
    let safe = escInHtml(text);
    let safeQ = escInHtml(q).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    return safe.replace(new RegExp(safeQ, 'gi'), m => `<mark>${m}</mark>`);
}

function escInHtml(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function selectInPart(partData) {
    hideInDropdown();
    $('#partSearchInput').val(partData.part_no);
    showExistingInPart(partData);
    $.get('../api/spareparts_inventory.php', { action: 'get_part_details_with_compatibility', part_no: partData.part_no }, function (res) {
        if (res.success && res.data) {
            if (res.data.compatibility && res.data.compatibility.length) {
                $('#compatibilityList').empty();
                res.data.compatibility.forEach(m => {
                    $('#compatibilityList').append(`<span class="compatibility-badge">${m}</span>`);
                });
            }
            if (currentlyScannedItem && currentlyScannedItem.part_no === res.data.part_no) {
                currentlyScannedItem.cost = parseFloat(res.data.cost) || 0;
                currentlyScannedItem.price = parseFloat(res.data.price) || 0;
            }
        }
    });
}

function commitInPartSearch(q) {
    $.get('../api/spareparts_inventory.php', { action: 'get_part_details_with_compatibility', part_no: q }, function (res) {
        hideInDropdown();
        if (res.success && res.data) {
            $('#partSearchInput').val(res.data.part_no);
            showExistingInPart(res.data);
        } else {
            $('#partSearchInput').val(q);
            showNewPartInEntry(q);
        }
    });
}

function showExistingInPart(data) {
    currentlyScannedItem = {
        part_no: data.part_no,
        description: data.description,
        brand: data.brand || '',
        cost: parseFloat(data.cost) || 0,
        price: parseFloat(data.price) || 0,
        isNew: false
    };
    const displayCost = currentlyScannedItem.cost;
    $('#partTitle').text(data.description || 'N/A');
    $('#partNoDisplay').html(`<span class="text-muted">${data.part_no}</span>`);
    $('#prevCostDisplay').text('₱' + displayCost.toLocaleString('en-PH', {minimumFractionDigits:2}));
    $('#scannedCost').val(displayCost.toFixed(2)).removeClass('cost-hike-input cost-lower-input');
    $('#scannedQty').val(1);
    $('#costChangeIndicator').empty();
    $('#costUpdateHint').addClass('d-none');
    $('#costHistoryLink').removeClass('d-none');
    $('#newPartBadge').addClass('d-none');
    $('#newPartFields').addClass('d-none');
    $('#newPartDesc').val('');
    $('#newPartBrand').val('');
    $('#compatibilityList').empty();
    if (data.compatibility && data.compatibility.length) {
        data.compatibility.forEach(m => {
            $('#compatibilityList').append(`<span class="compatibility-badge">${m}</span>`);
        });
    }
    $('#partDisplay').removeClass('new-part-mode').show();
    $('#scannedQty').focus().select();
}

function showNewPartInEntry(part_no) {
    currentlyScannedItem = {
        part_no: part_no,
        description: '',
        brand: '',
        cost: 0,
        price: 0,
        isNew: true
    };
    $('#partTitle').text('New Part Entry');
    $('#partNoDisplay').html(`<span class="fw-bold text-warning">${part_no}</span>`);
    $('#prevCostDisplay').text('N/A');
    $('#scannedCost').val('').removeClass('cost-hike-input cost-lower-input');
    $('#scannedQty').val(1);
    $('#costChangeIndicator').empty();
    $('#costUpdateHint').addClass('d-none');
    $('#costHistoryLink').addClass('d-none');
    $('#compatibilityList').empty();
    $('#newPartBadge').removeClass('d-none');
    $('#newPartFields').removeClass('d-none');
    $('#newPartDesc').val('');
    $('#newPartBrand').val('');
    $('#partDisplay').addClass('new-part-mode').fadeIn(300);
    setTimeout(() => $('#newPartDesc').focus(), 350);
}

function addCurrentPartToInCart() {
    if (!currentlyScannedItem) {
        Swal.fire('No Part Selected', 'Please search or scan a part number first.', 'warning');
        return;
    }
    let qty = parseInt($('#scannedQty').val()) || 1;
    let newCost = parseFloat($('#scannedCost').val());
    if (isNaN(newCost) || newCost < 0) {
        Swal.fire('Invalid Cost', 'Please enter a valid cost amount.', 'warning');
        $('#scannedCost').focus();
        return;
    }
    if (currentlyScannedItem.isNew) {
        let desc = $('#newPartDesc').val().trim();
        let brand = $('#newPartBrand').val().trim();
        if (!desc) {
            Swal.fire('Missing Description', 'Please enter the part description.', 'warning');
            $('#newPartDesc').focus();
            return;
        }
        currentlyScannedItem.description = desc;
        currentlyScannedItem.brand = brand;
    }
    let itemToAdd = {
        part_no: currentlyScannedItem.part_no,
        description: currentlyScannedItem.description,
        brand: currentlyScannedItem.brand,
        prev_cost: currentlyScannedItem.isNew ? 0 : currentlyScannedItem.cost,
        cost: newCost,
        price: currentlyScannedItem.price || 0,
        quantity: qty,
        isNew: currentlyScannedItem.isNew
    };
    let existing = inCart.findIndex(c => c.part_no === itemToAdd.part_no);
    if (existing >= 0) {
        inCart[existing].quantity += qty;
        inCart[existing].cost = newCost;
    } else {
        inCart.push(itemToAdd);
    }
    renderInCart();
    $('#partDisplay').fadeOut(200);
    $('#partSearchInput').val('').focus();
    currentlyScannedItem = null;
}

function renderInCart() {
    let html = '';
    let totalVal = 0, totalQty = 0;
    let brandGrouping = {};

    inCart.forEach((item, index) => {
        let subtotal = item.quantity * item.cost;
        totalVal += subtotal;
        totalQty += item.quantity;

        let brand = (item.brand || 'No Brand').trim();
        brandGrouping[brand] = (brandGrouping[brand] || 0) + item.quantity;

        let costClass = item.isNew ? 'text-warning' : '';
        let prevCostDisplay = item.isNew ? '<em class="text-muted small">New</em>' : '₱' + item.prev_cost.toFixed(2);
        html += `
        <tr>
            <td>
                <div class="fw-bold">${item.description} ${item.isNew ? '<span class="badge-new-part">NEW</span>' : ''}</div>
                <div class="small text-muted">${item.part_no}${item.brand ? ' · ' + item.brand : ''}</div>
            </td>
            <td class="text-center">
                <input type="number" class="form-control form-control-sm mx-auto text-center"
                    style="width:70px;" value="${item.quantity}" min="1"
                    onchange="updateInCartItem(${index}, 'quantity', this.value)">
            </td>
            <td class="text-end">${prevCostDisplay}</td>
            <td class="text-end">
                <input type="number" step="0.01" class="form-control form-control-sm ms-auto text-end ${costClass}"
                    style="width:110px;" value="${item.cost.toFixed(2)}"
                    onchange="updateInCartItem(${index}, 'cost', this.value)">
            </td>
            <td class="text-end fw-bold">₱${subtotal.toLocaleString('en-PH', {minimumFractionDigits:2})}</td>
            <td class="text-center">
                <button class="btn btn-sm btn-link text-danger" onclick="removeInFromCart(${index})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>`;
    });
    if (inCart.length === 0) {
        html = `<tr><td colspan="6" class="text-center text-muted py-4">No items added yet.</td></tr>`;
    }
    $('#cartBody').html(html);
    
    // Render Brand Summary
    let brandHtml = '';
    Object.keys(brandGrouping).sort().forEach(b => {
        brandHtml += `<div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted"><i class="bi bi-tag me-1"></i>${b}</span>
                        <span class="fw-bold">${brandGrouping[b]}</span>
                      </div>`;
    });
    $('#brandSummaryContainer').html(brandHtml);
    
    $('#summaryTotalQty').text(totalQty);
    $('#summaryTotalValue').text('₱' + totalVal.toLocaleString('en-PH', {minimumFractionDigits:2}));
}

function updateInCartItem(index, key, value) {
    if (key === 'quantity') inCart[index][key] = parseInt(value) || 1;
    else inCart[index][key] = parseFloat(value) || 0;
    renderInCart();
}

function removeInFromCart(index) {
    inCart.splice(index, 1);
    renderInCart();
}


function showInCostHistory(part_no) {
    $('#historyPartNo').text(part_no);
    $('#costHistoryBody').html('<tr><td colspan="4" class="text-center text-muted py-3">Loading...</td></tr>');
    bootstrap.Modal.getOrCreateInstance(document.getElementById('costHistoryModal')).show();
    $.get('../api/spareparts_inventory.php', { action: 'get_price_history', part_no: part_no }, function (res) {
        if (res.success && res.data && res.data.length) {
            let html = '';
            res.data.forEach(h => {
                html += `<tr>
                    <td>${h.transaction_date || h.date || ''}</td>
                    <td class="fw-bold">₱${parseFloat(h.cost||0).toFixed(2)}</td>
                    <td>${h.invoice_no || '—'}</td>
                    <td>${h.supplier || '—'}</td>
                </tr>`;
            });
            $('#costHistoryBody').html(html);
        } else {
            $('#costHistoryBody').html('<tr><td colspan="4" class="text-center text-muted py-3">No history found.</td></tr>');
        }
    });
}

function submitStockInAction() {
    let invoice = $('#invoiceNo').val().trim();
    if (!invoice) {
        Swal.fire('Missing Invoice', 'Please enter an Invoice/DR number.', 'error');
        $('#invoiceNo').focus();
        return;
    }
    if (inCart.length === 0) {
        Swal.fire('Empty List', 'Please add at least one part.', 'info');
        return;
    }

    let hikeParts = inCart.filter(i => !i.isNew && i.cost > i.prev_cost);
    let hikeMsg = hikeParts.length ? `<br><small class="text-danger"><i class="bi bi-exclamation-triangle"></i> ${hikeParts.length} part(s) have price increases. The master cost will be updated.</small>` : '';

    Swal.fire({
        title: 'Confirm Receipt?',
        html: `Recording <strong>${inCart.length}</strong> unique part(s) into inventory.${hikeMsg}`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#004d40',
        confirmButtonText: 'Yes, Confirm Receipt'
    }).then(result => {
        if (!result.isConfirmed) return;

        $('#confirmReceiptBtnInner').html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');
        $('#submitStockIn').prop('disabled', true);

        $.ajax({
            url: '../api/spareparts_inventory.php',
            method: 'POST',
            data: {
                action: 'add_multiple_parts_in',
                items: JSON.stringify(inCart),
                invoice_no: invoice,
                supplier_source: $('#supplier').val().trim(),
                payment_mode: $('#paymentMode').val(),
                date_in: $('#dateReceived').val()
            },
            success: function (response) {
                $('#confirmReceiptBtnInner').html('<i class="bi bi-save me-2"></i> CONFIRM RECEIPT');
                $('#submitStockIn').prop('disabled', false);
                if (response.success) {
                    Swal.fire({
                        title: 'Receipt Confirmed!',
                        html: 'Stock received and inventory updated successfully.',
                        icon: 'success',
                        confirmButtonColor: '#004d40'
                    }).then(() => {
                        bootstrap.Modal.getOrCreateInstance(document.getElementById('inventoryInModal')).hide();
                        if (typeof updateWarehouseSummary === 'function') updateWarehouseSummary();
                        if (typeof updateSalesSummary === 'function') updateSalesSummary();
                        inCart = [];
                        renderInCart();
                        $('#invoiceNo, #supplier').val('');
                        $('#partSearchInput').val('');
                        $('#partDisplay').hide();
                    });
                } else {
                    Swal.fire('Error', response.message || 'Failed to save.', 'error');
                }
            },
            error: function () {
                $('#confirmReceiptBtnInner').html('<i class="bi bi-save me-2"></i> CONFIRM RECEIPT');
                $('#submitStockIn').prop('disabled', false);
                Swal.fire('Error', 'Failed to communicate with server.', 'error');
            }
        });
    });
}

// Initial Call
$(document).ready(function() {
    initInventoryIn();

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
