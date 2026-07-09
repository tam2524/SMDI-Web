/* ============================================================
 *  SPAREPARTS STOCK CARD MODULE  — spareparts_stock_card.js
 *  Includes: Global Search + Filters (Low Price, Low Stock, Bin)
 * ============================================================ */

function formatCurrency(amount) {
    return Number(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// ─── Active filter state ──────────────────────────────────────
window._stockCardFilter = {
    type: '',          // 'low_price' | 'low_stock' | 'bin' | ''
    binLocation: ''
};

// ─── Fetch + render results from server ──────────────────────
function loadStockCardResults(params) {
    const $list = $('#partList');
    $list.html(`
        <div class="col-12 text-center py-5">
            <div class="spinner-border text-primary"></div>
            <p class="mt-2 text-muted">Loading items…</p>
        </div>`);

    $.get('../api/spareparts_inventory.php?action=search_parts_global', params, function(response) {
        if (response.success && response.data.length > 0) {
            renderStockCardResults(response.data);
        } else {
            $list.html(`
                <div class="col-12 text-center py-5">
                    <i class="bi bi-search text-muted fs-1 mb-3 d-block"></i>
                    <p class="text-muted">No parts found matching your criteria.</p>
                </div>`);
        }
    }, 'json').fail(function() {
        $list.html(`
            <div class="col-12 text-center py-5">
                <i class="bi bi-wifi-off text-danger fs-1 mb-3 d-block"></i>
                <p class="text-danger">Network error. Please try again.</p>
            </div>`);
    });
}

function buildQueryParams() {
    const term = ($('#globalSearchInput').val() || '').trim();
    const f    = window._stockCardFilter;
    const params = {};
    if (term)        params.term         = term;
    if (f.type)      params.filter       = f.type;
    if (f.type === 'bin' && f.binLocation) params.bin_location = f.binLocation;
    return params;
}

function searchStockCardManual(query) {
    if (!query) return;
    loadStockCardResults(buildQueryParams());
}

// ─── Triggered when a filter button is clicked ───────────────
function applyStockCardFilter(type) {
    const f = window._stockCardFilter;

    if (type === f.type) {
        // toggle off
        f.type        = '';
        f.binLocation = '';
        $('#stockCardBinInput').slideUp(200).val('');
    } else {
        f.type = type;
        if (type !== 'bin') {
            f.binLocation = '';
            $('#stockCardBinInput').slideUp(200).val('');
        }
    }

    // Refresh button styles
    $('.sc-filter-btn').each(function() {
        const btn = $(this).data('filter');
        if (btn === f.type) {
            $(this).addClass('active');
        } else {
            $(this).removeClass('active');
        }
    });

    if (type === 'bin' && f.type === 'bin') {
        $('#stockCardBinInput').slideDown(200).focus();
        return; // wait for user to type
    }

    const params = buildQueryParams();
    if (!$.isEmptyObject(params)) {
        loadStockCardResults(params);
    }
}

// ─── Pagination state ────────────────────────────────────────
let stockCardFullData = [];
let stockCardCurrentPage = 1;
const STOCK_CARD_PAGE_SIZE = 15;

function renderPagination(pagId, infoId, totalItems, currentPage, onChangePage) {
    const totalPages = Math.max(1, Math.ceil(totalItems / STOCK_CARD_PAGE_SIZE));
    const pag = document.getElementById(pagId);
    const info = document.getElementById(infoId);
    if (!pag || !info) return;

    const start = totalItems === 0 ? 0 : (currentPage - 1) * STOCK_CARD_PAGE_SIZE + 1;
    const end = Math.min(currentPage * STOCK_CARD_PAGE_SIZE, totalItems);
    info.textContent = totalItems === 0 ? 'No items found' : `Showing ${start}-${end} of ${totalItems} items`;

    pag.innerHTML = '';
    if (totalItems === 0) return;

    if (totalItems > 0) {
        const mkItem = (label, pageNumber, disabled, active) => {
            const li = document.createElement('li');
            li.className = `page-item ${disabled ? 'disabled' : ''} ${active ? 'active' : ''}`;
            const a = document.createElement('a');
            a.className = `page-link rounded-pill px-3 ${active ? 'text-white' : ''}`;
            a.href = '#';
            a.dataset.page = pageNumber;
            a.style.cssText = active ? 'background:#004d40;border-color:#004d40;' : 'border-color:#dee2e6;';
            a.innerHTML = label;
            li.appendChild(a);
            return li;
        };

        if (totalPages > 1) {
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
        }

        // Add "Go to page" input
        const jumpLi = document.createElement('li');
        jumpLi.className = 'page-item ms-3 d-flex align-items-center';
        jumpLi.innerHTML = `
            <span class="small text-muted me-2">Go to:</span>
            <input type="number" class="form-control form-control-sm text-center jump-to-page" 
                   value="${currentPage}" min="1" max="${totalPages}" 
                   style="width: 55px; border-radius: 20px; padding: 2px 5px; height: 28px;">
        `;
        pag.appendChild(jumpLi);
    }

    $(pag).off('click', '.page-link').on('click', '.page-link', function (e) {
        e.preventDefault();
        const pg = parseInt($(this).data('page'));
        if (pg && pg >= 1 && pg <= totalPages && pg !== currentPage) {
            onChangePage(pg);
        }
    });

    $(pag).off('keypress', '.jump-to-page').on('keypress', '.jump-to-page', function (e) {
        if (e.which === 13) {
            const pg = parseInt($(this).val());
            if (pg && pg >= 1 && pg <= totalPages && pg !== currentPage) {
                onChangePage(pg);
            } else {
                $(this).val(currentPage);
            }
        }
    });
}

// ─── Render results table ─────────────────────────────────────
function renderStockCardResults(data) {
    if (!data) return;
    stockCardFullData = data;
    const f = window._stockCardFilter;

    const startIdx = (stockCardCurrentPage - 1) * STOCK_CARD_PAGE_SIZE;
    const pagedData = data.slice(startIdx, startIdx + STOCK_CARD_PAGE_SIZE);

    let rowsHtml = '';
    pagedData.forEach(item => {
        const isLow      = Number(item.current_stock) <= Number(item.min_stock || 1);
        const stockClass = isLow ? 'text-danger fw-bold' : 'text-success fw-bold';
        const lowBadge   = isLow ? `<span class="badge bg-danger ms-1" style="font-size:0.6rem;">LOW</span>` : '';
        const lowPriceBadge = Number(item.cost) <= 5
            ? `<span class="badge bg-warning text-dark ms-1" style="font-size:0.6rem;">≤ ₱5</span>` : '';

        rowsHtml += `
            <tr class="align-middle">
                <td class="ps-4">
                    <div class="fw-bold text-dark">${item.part_no}</div>
                    <div class="small text-muted">${item.brand}</div>
                </td>
                <td>
                    <div class="text-truncate-2 small" style="max-width: 300px;">${item.description}</div>
                </td>
                <td class="text-center">
                    <span class="badge bg-light text-dark border fw-bold" style="font-size: 0.75rem;">
                        <i class="bi bi-building me-1 text-primary"></i>${item.current_branch}
                    </span>
                </td>
                <td class="text-center">
                    <div class="small fw-bold text-secondary">
                        <i class="bi bi-geo-alt me-1 text-muted"></i>${item.bin_location || 'N/A'}
                    </div>
                </td>
                <td class="text-end fw-bold text-muted small">
                    ₱${formatCurrency(item.cost)}${lowPriceBadge}
                </td>
                <td class="text-end fw-bold text-dark">
                    ₱${formatCurrency(item.price)}
                </td>
                <td class="text-center">
                    <span class="${stockClass}">${item.current_stock}</span>
                    <small class="text-muted ms-1">pcs</small>${lowBadge}
                </td>
                <td class="pe-4 text-center">
                    <button class="btn btn-sm btn-outline-primary fw-bold rounded-pill px-3"
                            onclick="showStockCard('${item.part_no}', '${item.current_branch}')">
                        <i class="bi bi-card-list me-1"></i> Stock Card
                    </button>
                </td>
            </tr>
        `;
    });

    const tableHtml = `
        <div class="d-flex justify-content-between align-items-center mb-2 px-1">
            <span id="stockCardPageInfo" class="text-muted small"></span>
        </div>
        <div class="card border-0 shadow-sm overflow-hidden mb-3" style="border-radius: 16px;">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr class="text-uppercase small fw-bold text-muted">
                            <th class="ps-4 py-3">Part Info</th>
                            <th class="py-3">Description</th>
                            <th class="text-center py-3">Branch</th>
                            <th class="text-center py-3">Bin Location</th>
                            <th class="text-end py-3">Cost</th>
                            <th class="text-end py-3">Price</th>
                            <th class="text-center py-3">In Stock</th>
                            <th class="text-center py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>${rowsHtml}</tbody>
                </table>
            </div>
        </div>
        <nav class="d-flex justify-content-end">
            <ul id="stockCardPagination" class="pagination pagination-sm mb-0"></ul>
        </nav>
    `;
    $('#partList').html(tableHtml);

    renderPagination('stockCardPagination', 'stockCardPageInfo', data.length, stockCardCurrentPage, pg => {
        stockCardCurrentPage = pg;
        renderStockCardResults(stockCardFullData);
    });
}

// ─── Inject the filter bar into the page ─────────────────────
function injectFilterBar() {
    // Only inject once
    if ($('#stockCardFilterBar').length > 0) return;

    const filterBarHtml = `
        <div id="stockCardFilterBar" class="mb-3 d-flex flex-wrap align-items-center gap-2">
            <span class="text-muted small fw-semibold me-1">
                <i class="bi bi-funnel me-1"></i>Quick Filters:
            </span>

            <button class="btn btn-sm sc-filter-btn rounded-pill px-3 fw-semibold border"
                    data-filter="low_price"
                    onclick="applyStockCardFilter('low_price')"
                    title="Show parts with cost price ≤ ₱5.00"
                    id="btnFilterLowPrice">
                <i class="bi bi-currency-exchange me-1 text-warning"></i>
                Cost ≤ ₱5
            </button>

            <button class="btn btn-sm sc-filter-btn rounded-pill px-3 fw-semibold border"
                    data-filter="low_stock"
                    onclick="applyStockCardFilter('low_stock')"
                    title="Show parts at or below minimum stock level"
                    id="btnFilterLowStock">
                <i class="bi bi-graph-down-arrow me-1 text-danger"></i>
                Low Stocks
            </button>

            <button class="btn btn-sm sc-filter-btn rounded-pill px-3 fw-semibold border"
                    data-filter="bin"
                    onclick="applyStockCardFilter('bin')"
                    title="Filter by bin / shelf location"
                    id="btnFilterBin">
                <i class="bi bi-geo-alt me-1 text-info"></i>
                By Bin Location
            </button>

            <div id="stockCardBinInput" class="input-group input-group-sm" style="display:none; max-width: 240px;">
                <span class="input-group-text bg-white border-end-0">
                    <i class="bi bi-search text-muted"></i>
                </span>
                <input type="text" id="binLocationSearch" class="form-control border-start-0 ps-0"
                       placeholder="e.g. A-01, SHELF-B…"
                       style="border-radius: 0 20px 20px 0;">
            </div>
        </div>

        <style>
            .sc-filter-btn {
                background: #fff;
                color: #555;
                transition: all .2s;
            }
            .sc-filter-btn:hover,
            .sc-filter-btn.active {
                background: #004d40;
                color: #fff !important;
                border-color: #004d40 !important;
            }
            .sc-filter-btn.active .bi {
                color: #fff !important;
            }
        </style>
    `;

    // Insert filter bar just before the #partList container
    if ($('#partList').length) {
        $('#partList').before(filterBarHtml);
    } else if ($('#searchResultsArea').length) {
        $('#searchResultsArea').before(filterBarHtml);
    }
}

// ─── showStockCard (detail modal) ────────────────────────────
function showStockCard(partNo, branch) {
    branch = branch || '';
    $.get('../api/spareparts_inventory.php?action=get_stock_card_data', { part_no: partNo, branch: branch }, response => {
        if (response.success) {
            const d   = response.data;
            const inv = d.inventory;

            $('#stockCardPartNo').text(inv.part_no);
            $('#stockCardDescription').text(inv.description);
            $('#stockCardBrand').text(inv.brand);
            $('#stockCardQty').text(inv.current_stock);
            $('#stockCardBin').text(inv.bin_location || 'N/A');
            $('#stockCardCost').text('₱' + formatCurrency(inv.cost));
            $('#stockCardPrice').text('₱' + formatCurrency(inv.price));

            // Thumbnail Image
            if (inv.image_url) {
                $('#stockCardImage').html(`<img src="../${inv.image_url}" class="img-fluid rounded" style="max-height: 100%; max-width: 100%; object-fit: contain;">`);
            } else {
                $('#stockCardImage').html('<i class="bi bi-image text-muted" style="font-size: 4rem;"></i>');
            }

            // Color Code Stock & Low Stock Alert
            if (Number(inv.current_stock) <= Number(inv.min_stock)) {
                $('#stockCardQty').addClass('text-danger');
                $('#stockCardQty').append(' <span class="badge bg-danger ms-2" style="font-size: 0.6rem;">LOW STOCK</span>');
            } else {
                $('#stockCardQty').removeClass('text-danger');
            }

            /* Compatibility section removed as per requirements */

            // Movement Log
            let moveHtml = '';
            d.transactions.forEach(t => {
                let qty = Number(t.quantity);
                let isIncoming = (t.type === 'IN' || t.type === 'TRANSFER_IN' || t.type === 'RETURN' || (t.type === 'ADJUSTMENT' && qty > 0));
                let typeClass  = isIncoming ? 'text-success' : 'text-danger';
                let icon       = isIncoming ? 'bi-arrow-down-left-circle' : 'bi-arrow-up-right-circle';
                let typeText   = t.type;
                let fromTo     = '';

                if (t.type === 'IN') {
                    typeText = t.from_location === 'Initial Encoding' ? 'Initial Stock' : 'Delivered';
                    if (window.canDelete && t.from_location !== 'Initial Encoding') {
                        typeText += ` <button class="btn btn-sm btn-outline-danger ms-2 delete-in-btn py-0 px-1" style="font-size: 0.75rem;" data-id="${t.id}" data-part="${partNo}" data-qty="${qty}" data-branch="${branch}" title="Delete Receive In"><i class="bi bi-trash"></i></button>`;
                    }
                    fromTo   = t.from_location;
                } else if (t.type === 'OUT') {
                    typeText = 'Sold'; fromTo = t.customer_name;
                } else if (t.type === 'RETURN') {
                    typeText = 'Returned (CM)'; fromTo = t.customer_name;
                } else if (t.type === 'TRANSFER_IN' || t.type === 'TRANSFER_OUT') {
                    typeText = 'Transferred'; fromTo = (t.to_location || t.from_location);
                } else if (t.type === 'ADJUSTMENT') {
                    typeText = 'Adjustment'; fromTo = t.reason || 'Manual Update';
                } else {
                    fromTo = (t.to_location || t.from_location);
                }

                let displayQty = isIncoming ? '+' + Math.abs(qty) : '-' + Math.abs(qty);
                let dateOnly = t.transaction_date.split(' ')[0];
                let dateDisplay = dateOnly;
                if (window.canDelete) {
                    dateDisplay = `
                        <div class="d-flex align-items-center gap-1">
                            <span>${dateOnly}</span>
                            <button class="btn btn-sm btn-link p-0 text-primary edit-tx-date-btn" 
                                    data-id="${t.id}" 
                                    data-date="${dateOnly}" 
                                    data-part="${partNo}" 
                                    data-branch="${branch}" 
                                    title="Edit Date">
                                <i class="bi bi-pencil-square" style="font-size: 0.85rem;"></i>
                            </button>
                        </div>
                    `;
                }

                moveHtml += `
                    <tr>
                        <td class="ps-4 py-3 text-muted">${dateDisplay}</td>
                        <td class="${typeClass} fw-bold py-3"><i class="bi ${icon} me-2"></i>${typeText}</td>
                        <td class="text-center py-3 fw-bold ${typeClass}">${displayQty}</td>
                        <td class="py-3">${fromTo || '-'}</td>
                        <td class="pe-4 py-3 text-muted">${t.recovered_transfer_no || t.transfer_no || t.or_number || t.invoice_no || '-'}</td>
                    </tr>
                `;
            });
            $('#stockCardMovementBody').html(moveHtml || '<tr><td colspan="5" class="text-center text-muted py-5">No movement found for this part in your branch.</td></tr>');

            // Price History
            let histHtml = '';
            d.price_history.forEach((h, index) => {
                let isLatest = index === 0;
                let rowStyle = isLatest ? 'style="background-color: rgba(25, 135, 84, 0.08);"' : '';
                let latestBadge = isLatest ? '<span class="badge bg-success text-white ms-2" style="font-size: 0.65rem; padding: 2px 6px;">LATEST</span>' : '';
                histHtml += `
                    <tr ${rowStyle}>
                        <td class="ps-4 py-3 text-muted">${h.transaction_date.split(' ')[0]}</td>
                        <td class="py-3 fw-bold d-flex align-items-center">${h.supplier}${latestBadge}</td>
                        <td class="text-end py-3 text-primary fw-bold">₱${formatCurrency(h.cost)}</td>
                        <td class="text-end py-3 text-success fw-bold">₱${formatCurrency(h.price)}</td>
                        <td class="py-3 fw-semibold text-secondary small">${h.change_reason || '-'}</td>
                        <td class="py-3 fw-bold text-dark small">${h.changed_by || '-'}</td>
                        <td class="pe-4 py-3 text-muted">${h.invoice_no || '-'}</td>
                    </tr>
                `;
            });
            $('#stockCardHistoryBody').html(histHtml || '<tr><td colspan="7" class="text-center text-muted py-5">No price history found.</td></tr>');

            bootstrap.Modal.getOrCreateInstance(document.getElementById('stockCardModal')).show();
        } else {
            console.error('Stock card data load failed:', response.message);
            showErrorModal(response.message || 'Failed to load stock card data.');
        }
    }, 'json').fail((jqXHR, textStatus, errorThrown) => {
        console.error('Stock card API request failed:', textStatus, errorThrown);
        showErrorModal('Network error while fetching stock card. Please try again.');
    });
}

// ─── Barcode scanner listener ─────────────────────────────────
$(document).on('keypress', function(e) {
    if (!$(':focus').is('input, textarea, select')) {
        if (e.which == 13) {
            let barcode = window._barcodeBuffer || '';
            window._barcodeBuffer = '';
            if (barcode.length > 3) {
                showStockCard(barcode);
            }
        } else {
            window._barcodeBuffer = (window._barcodeBuffer || '') + String.fromCharCode(e.which);
            clearTimeout(window._barcodeTimeout);
            window._barcodeTimeout = setTimeout(() => { window._barcodeBuffer = ''; }, 1000);
        }
    }
});

// ─── Document ready ───────────────────────────────────────────
$(document).ready(function() {

    // Inject filter bar
    injectFilterBar();

    // Search button
    $('#globalSearchBtn').on('click', function() {
        const val = $('#globalSearchInput').val().trim();
        if (val || window._stockCardFilter.type) {
            loadStockCardResults(buildQueryParams());
        }
    });

    // Search on Enter
    $('#globalSearchInput').on('keypress', function(e) {
        if (e.which === 13) {
            const val = $(this).val().trim();
            if (val || window._stockCardFilter.type) {
                loadStockCardResults(buildQueryParams());
            }
        }
    });

    // Bin location search — trigger on Enter or after brief pause
    $(document).on('keypress', '#binLocationSearch', function(e) {
        if (e.which === 13) {
            window._stockCardFilter.binLocation = $(this).val().trim();
            loadStockCardResults(buildQueryParams());
        }
    });

    $(document).on('input', '#binLocationSearch', function() {
        clearTimeout(window._binSearchTimeout);
        const val = $(this).val().trim();
        window._stockCardFilter.binLocation = val;
        if (val.length >= 2) {
            window._binSearchTimeout = setTimeout(function() {
                loadStockCardResults(buildQueryParams());
            }, 600);
        }
    });

    // Stock card button in other modules
    $(document).on('click', '.view-stock-card-btn', function() {
        const partNo = $(this).data('part-no');
        if (partNo) showStockCard(partNo);
    });

    // Edit transaction date (For Spareparts-Admin / Admin)
    $(document).on('click', '.edit-tx-date-btn', function(e) {
        e.preventDefault();
        const txId = $(this).data('id');
        const currentDate = $(this).data('date');
        const partNo = $(this).data('part');
        const branch = $(this).data('branch');

        Swal.fire({
            title: 'Edit Date of Activity Log',
            html: `
                <div class="text-start p-2">
                    <label class="form-label fw-bold small text-muted text-uppercase mb-1" style="font-size: 0.75rem;">New Transaction Date</label>
                    <input type="date" id="swalTxDate" class="form-control form-control-lg border-2" value="${currentDate}" style="border-radius: 8px;">
                    <p class="text-muted small mt-2 mb-0"><i class="bi bi-info-circle me-1"></i>Note: Editing this date will update the logs and synchronize it across any linked sale aging entries or transfer entries.</p>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: '<i class="bi bi-check-circle me-1"></i>Save Changes',
            confirmButtonColor: '#004d40',
            cancelButtonText: 'Cancel',
            cancelButtonColor: '#6c757d',
            customClass: {
                confirmButton: 'px-4 py-2 rounded-pill fw-bold',
                cancelButton: 'px-4 py-2 rounded-pill fw-bold'
            },
            preConfirm: () => {
                const newDate = $('#swalTxDate').val();
                if (!newDate) {
                    Swal.showValidationMessage('Please select a valid date.');
                    return false;
                }
                return newDate;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const newDate = result.value;
                
                // Show loading spinner
                Swal.fire({
                    title: 'Updating transaction date...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: '../api/spareparts_inventory.php',
                    type: 'POST',
                    data: {
                        action: 'edit_transaction_date',
                        id: txId,
                        new_date: newDate
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Success!',
                                text: response.message,
                                icon: 'success',
                                confirmButtonColor: '#004d40',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                // Refresh the stock card modal view without page reload
                                showStockCard(partNo, branch);
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: response.message,
                                icon: 'error',
                                confirmButtonColor: '#d33'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: 'Error',
                            text: 'Failed to communicate with the server.',
                            icon: 'error',
                            confirmButtonColor: '#d33'
                        });
                    }
                });
            }
        });
    });

    // Bulk Price Preview Handler
    $('#bulkPriceForm').on('submit', function(e) {
        e.preventDefault();
        
        let formData = new FormData(this);
        
        Swal.fire({
            title: 'Parsing Excel/CSV File...',
            text: 'Please wait while we extract pricing data.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        $.ajax({
            url: '../api/bulk_price_preview.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                Swal.close();
                if (response.success) {
                    let html = '';
                    if (response.data.length === 0) {
                        html = '<tr><td colspan="7" class="text-center py-4 text-muted">No pricing changes detected. All parts in file match current inventory prices.</td></tr>';
                        $('#confirmBulkBtn').prop('disabled', true);
                    } else {
                        response.data.forEach(item => {
                            let statusBadge = '';
                            if (item.status === 'Update') {
                                statusBadge = '<span class="badge bg-success">UPDATE</span>';
                            } else {
                                statusBadge = '<span class="badge bg-danger">NOT FOUND</span>';
                            }
                            
                            html += `
                                <tr>
                                    <td class="fw-bold">${item.part_no}</td>
                                    <td>${item.description}</td>
                                    <td class="text-end text-muted">₱${formatCurrency(item.current_cost)}</td>
                                    <td class="text-end text-success fw-bold">₱${formatCurrency(item.new_cost)}</td>
                                    <td class="text-end text-muted">₱${formatCurrency(item.current_price)}</td>
                                    <td class="text-end text-primary fw-bold">₱${formatCurrency(item.new_price)}</td>
                                    <td class="text-center">${statusBadge}</td>
                                </tr>
                            `;
                        });
                        $('#confirmBulkBtn').prop('disabled', response.total_updates === 0);
                    }
                    $('#bulkPreviewTbody').html(html);
                    $('#previewSummaryText').text(`${response.total_updates} item(s) will be updated. Unmatched items will be ignored.`);
                    $('#uploadView').addClass('d-none');
                    $('#previewView').removeClass('d-none');
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: response.message,
                        icon: 'error',
                        confirmButtonColor: '#d33'
                    });
                }
            },
            error: function() {
                Swal.close();
                Swal.fire({
                    title: 'Error',
                    text: 'Failed to upload and preview Excel file. Ensure file is not corrupt and formats are correct.',
                    icon: 'error',
                    confirmButtonColor: '#d33'
                });
            }
        });
    });

    // Back to Upload View
    $('#btnBackToUpload').on('click', function() {
        $('#previewView').addClass('d-none');
        $('#uploadView').removeClass('d-none');
    });

    // Reset Modal on Close/Hide
    $('#bulkPriceUploadModal').on('hidden.bs.modal', function() {
        $('#bulkPriceForm')[0].reset();
        $('#bulkPreviewTbody').html('');
        $('#previewView').addClass('d-none');
        $('#uploadView').removeClass('d-none');
    });

    // Cancel Preview / Reset
    $('#cancelBulkBtn').on('click', function() {
        $('#bulkPriceUploadModal').modal('hide');
    });

    // Confirm Bulk Pricing Upload
    $('#confirmBulkBtn').on('click', function() {
        const reason = $('#bulkChangeReason').val();
        if (!reason) {
            Swal.fire({
                title: 'Reason Required',
                text: 'Please select a reason for change before applying updates.',
                icon: 'warning',
                confirmButtonColor: '#004d40'
            });
            return;
        }
        const changedBy = $('#bulkChangedBy').val();

        Swal.fire({
            title: 'Apply Pricing Updates?',
            text: "This will update the cost and selling prices of the matched parts and log this in the price history. This action cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#004d40',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Apply Pricing!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Applying updates...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: '../api/bulk_price_update.php',
                    type: 'POST',
                    data: {
                        change_reason: reason,
                        changed_by: changedBy
                    },
                    dataType: 'json',
                    success: function(response) {
                        Swal.close();
                        if (response.success) {
                            Swal.fire({
                                title: 'Success!',
                                text: response.message,
                                icon: 'success',
                                confirmButtonColor: '#004d40'
                            }).then(() => {
                                $('#bulkPriceUploadModal').modal('hide');
                                // Refresh list if search is active
                                if (typeof buildQueryParams === 'function' && typeof loadStockCardResults === 'function') {
                                    const qParams = buildQueryParams();
                                    if (!$.isEmptyObject(qParams)) {
                                        loadStockCardResults(qParams);
                                    }
                                }
                                if (typeof loadPricingData === 'function') {
                                    loadPricingData();
                                }
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: response.message,
                                icon: 'error',
                                confirmButtonColor: '#d33'
                            });
                        }
                    },
                    error: function() {
                        Swal.close();
                        Swal.fire({
                            title: 'Error',
                            text: 'Failed to communicate with the server to apply updates.',
                            icon: 'error',
                            confirmButtonColor: '#d33'
                        });
                    }
                });
            }
        });
    });
});
