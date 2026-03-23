const pageData = [{current_stock: 1, min_stock: 0, part_no: 'A', brand: 'B', description: 'C', cost: 1, price: 2, current_branch: 'D', bin_location: 'E', id: 1}];
function formatCurrency(v){ return v; }
function escapeHtml(v){ return v; }
const canDelete = true;
pageData.forEach(item => {
    const stockLevel = Number(item.current_stock);
    const minStock   = Number(item.min_stock || 0);
    const isLowStock = stockLevel <= minStock;
    const stockClass = isLowStock ? 'text-danger fw-bold' : 'fw-bold text-dark';
    const statusBadge = isLowStock
        ? '<span class="badge rounded-pill bg-danger">Low Stock</span>'
        : '<span class="badge rounded-pill bg-primary">In Stock</span>';
    const totalValue = formatCurrency(stockLevel * Number(item.cost || 0));
    const isBranchPage = true; // or false

    let rowHtml = `
    <tr class="align-middle border-bottom">
        <td class="ps-4 py-3">
            <div class="fw-bold text-dark" style="font-size:0.875rem;">${escapeHtml(item.part_no)}</div>
            <div class="small text-muted">${escapeHtml(item.brand)}</div>
        </td>
        <td class="py-3" style="max-width: 260px;">
            <div class="small text-dark" style="line-height:1.4;">${escapeHtml(item.description)}</div>
        </td>
        ${!isBranchPage ? `
        <td class="text-center py-3">
            <span class="badge bg-light text-dark border fw-semibold px-2 py-1" style="font-size:0.75rem;">
                <i class="bi bi-building me-1 text-primary" style="font-size:0.7rem;"></i>${escapeHtml(item.current_branch || 'N/A')}
            </span>
        </td>
        ` : `
        <td class="text-center py-3">
            <span class="badge bg-light text-dark border fw-semibold px-2 py-1" style="font-size:0.75rem;">
                <i class="bi bi-geo-alt-fill me-1 text-primary" style="font-size:0.7rem;"></i>${escapeHtml(item.bin_location || 'N/A')}
            </span>
        </td>
        `}
        <td class="text-center py-3">
            <span class="${stockClass}" style="font-size:1rem;">${stockLevel}</span>
        </td>
        <td class="text-end py-3 text-muted" style="font-size:0.875rem;">${formatCurrency(item.cost)}</td>
        <td class="text-end py-3 fw-bold text-primary" style="font-size:0.875rem;">${formatCurrency(item.price)}</td>
        ${!isBranchPage ? `<td class="text-end py-3 fw-bold text-success" style="font-size:0.875rem;">${totalValue}</td>` : ''}
        <td class="text-center py-3">${statusBadge}</td>
        <td class="text-center pe-4 py-3">
            <div class="btn-group border rounded overflow-hidden" style="box-shadow:0 1px 4px rgba(0,0,0,0.08);">
                <button class="btn btn-sm show-stock-card-btn" data-part-no="${item.part_no}" data-branch="${item.current_branch}" title="Stock Card" style="background:#fff;">
                    <i class="bi bi-card-list text-primary"></i>
                </button>
                <button class="btn btn-sm edit-part-btn border-start" data-id="${item.id}" title="Edit Part" style="background:#fff;">
                    <i class="bi bi-pencil text-secondary"></i>
                </button>
                ${canDelete ? `
                <button class="btn btn-sm delete-part-btn border-start" data-id="${item.id}" title="Delete" style="background:#fff;">
                    <i class="bi bi-trash text-danger"></i>
                </button>` : ''}
            </div>
        </td>
    </tr>`;
    console.log("Success");
});
