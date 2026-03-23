function formatCurrency(amount) { return Number(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

function showStockCard(partNo, branch = '') {
    $.get('../api/spareparts_inventory.php?action=get_stock_card_data', { part_no: partNo, branch: branch }, response => {
        if (response.success) {
            const d = response.data;
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
            if (inv.current_stock <= inv.min_stock) {
                $('#stockCardQty').addClass('text-danger');
                $('#stockCardQty').append(' <span class="badge bg-danger ms-2" style="font-size: 0.6rem;">LOW STOCK</span>');
            } else {
                $('#stockCardQty').removeClass('text-danger');
            }

            /* Compatibility section removed as per requirements */

            // Movement Log
            let moveHtml = '';
            d.transactions.forEach(t => {
                let qtyStr = t.quantity.toString();
                let qty = Number(t.quantity);
                let isIncoming = (t.type === 'IN' || t.type === 'TRANSFER_IN' || t.type === 'RETURN' || (t.type === 'ADJUSTMENT' && qty > 0));
                
                let typeClass = isIncoming ? 'text-success' : 'text-danger';
                let icon = isIncoming ? 'bi-arrow-down-left-circle' : 'bi-arrow-up-right-circle';
                
                let typeText = t.type;
                let fromTo = '';
                
                if (t.type === 'IN') { 
                    typeText = t.from_location === 'Initial Encoding' ? 'Initial Stock' : 'Delivered'; 
                    fromTo = t.from_location; 
                }
                else if (t.type === 'OUT') { typeText = 'Sold'; fromTo = t.customer_name; }
                else if (t.type === 'RETURN') { typeText = 'Returned (CM)'; fromTo = t.customer_name; }
                else if (t.type === 'TRANSFER_IN' || t.type === 'TRANSFER_OUT') { typeText = 'Transferred'; fromTo = (t.to_location || t.from_location); }
                else if (t.type === 'ADJUSTMENT') { typeText = 'Adjustment'; fromTo = t.reason || 'Manual Update'; }
                else { fromTo = (t.to_location || t.from_location); }

                let displayQty = isIncoming ? '+' + Math.abs(qty) : '-' + Math.abs(qty);

                moveHtml += `
                    <tr>
                        <td class="ps-4 py-3 text-muted">${t.transaction_date.split(' ')[0]}</td>
                        <td class="${typeClass} fw-bold py-3"><i class="bi ${icon} me-2"></i>${typeText}</td>
                        <td class="text-center py-3 fw-bold ${typeClass}">${displayQty}</td>
                        <td class="py-3">${fromTo || '-'}</td>
                        <td class="pe-4 py-3 text-muted">${t.or_number || t.invoice_no || '-'}</td>
                    </tr>
                `;
            });
            $('#stockCardMovementBody').html(moveHtml || '<tr><td colspan="5" class="text-center text-muted py-5">No movement found for this part in your branch.</td></tr>');

            // Price History
            let histHtml = '';
            d.price_history.forEach(h => {
                histHtml += `
                    <tr>
                        <td class="ps-4 py-3 text-muted">${h.transaction_date.split(' ')[0]}</td>
                        <td class="py-3 fw-bold">${h.supplier}</td>
                        <td class="text-end py-3 text-primary fw-bold">₱${formatCurrency(h.cost)}</td>
                        <td class="pe-4 py-3 text-muted">${h.invoice_no || '-'}</td>
                    </tr>
                `;
            });
            $('#stockCardHistoryBody').html(histHtml || '<tr><td colspan="4" class="text-center text-muted py-5">No price history found.</td></tr>');

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

function searchStockCardManual(query) {
    if (!query) return;
    $('#partList').html('<div class="col-12 text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2">Searching items across all branches...</p></div>');
    
    $.get('../api/spareparts_inventory.php?action=search_parts_global', { term: query }, response => {
        if (response.success && response.data.length > 0) {
            renderStockCardResults(response.data);
        } else {
            $('#partList').html('<div class="col-12 text-center py-5"><i class="bi bi-search text-muted fs-1 mb-3 d-block"></i><p class="text-muted">No parts found matching "'+query+'".</p></div>');
        }
    }, 'json');
}

function renderStockCardResults(data) {
    let rowsHtml = '';
    data.forEach(item => {
        const isLow = Number(item.current_stock) <= Number(item.min_stock || 1);
        const stockClass = isLow ? 'text-danger fw-bold' : 'text-success fw-bold';
        
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
                    <div class="small fw-bold text-secondary">${item.bin_location || 'N/A'}</div>
                </td>
                <td class="text-end fw-bold text-dark">
                    ₱${formatCurrency(item.price)}
                </td>
                <td class="text-center">
                    <span class="${stockClass}">${item.current_stock}</span>
                    <small class="text-muted ms-1">pcs</small>
                </td>
                <td class="pe-4 text-center">
                    <button class="btn btn-sm btn-outline-primary fw-bold rounded-pill px-3" onclick="showStockCard('${item.part_no}', '${item.current_branch}')">
                        <i class="bi bi-card-list me-1"></i> Stock Card
                    </button>
                </td>
            </tr>
        `;
    });

    const tableHtml = `
        <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 16px;">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr class="text-uppercase small fw-bold text-muted">
                            <th class="ps-4 py-3">Part Info</th>
                            <th class="py-3">Description</th>
                            <th class="text-center py-3">Branch</th>
                            <th class="text-center py-3">Location</th>
                            <th class="text-end py-3">Price</th>
                            <th class="text-center py-3">In Stock</th>
                            <th class="text-center py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rowsHtml}
                    </tbody>
                </table>
            </div>
        </div>
    `;
    $('#partList').html(tableHtml);
}

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

$(document).ready(function() {
    $('#globalSearchBtn').on('click', function() {
        const val = $('#globalSearchInput').val().trim();
        if (val) searchStockCardManual(val);
    });

    $('#globalSearchInput').on('keypress', function(e) {
        if (e.which === 13) {
            const val = $(this).val().trim();
            if (val) searchStockCardManual(val);
        }
    });

    // If there's a part list, hook up buttons there too
    $(document).on('click', '.view-stock-card-btn', function() {
        const partNo = $(this).data('part-no');
        if (partNo) showStockCard(partNo);
    });
});
