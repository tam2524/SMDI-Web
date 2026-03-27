function updateWarehouseSummary() {
    fetch('../api/spareparts_dashboard_api.php?action=get_warehouse_summary')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const s = data.summary;
                const setT = (id, val) => {
                    const el = document.getElementById(id);
                    if (el) el.textContent = val;
                };
                setT('received-qty', Number(s.received.qty).toLocaleString());
                setT('received-amount', '₱' + Number(s.received.amount).toLocaleString(undefined, {minimumFractionDigits: 2}));
                setT('transferred-qty', Number(s.transferred.qty).toLocaleString());
                setT('transferred-amount', '₱' + Number(s.transferred.amount).toLocaleString(undefined, {minimumFractionDigits: 2}));
            }
        });
}

function updateSalesSummary() {
    fetch('../api/spareparts_dashboard_api.php?action=get_sales_summary')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const s = data.summary;
                const setT = (id, val) => {
                    const el = document.getElementById(id);
                    if (el) el.textContent = val;
                };
                setT('received-qty', Number(s.received.qty).toLocaleString());
                setT('received-amount', '₱' + Number(s.received.amount).toLocaleString(undefined, {minimumFractionDigits: 2}));
                setT('cash-amount', '₱' + Number(s.cash.amount).toLocaleString(undefined, {minimumFractionDigits: 2}));
                setT('charge-amount', '₱' + Number(s.charge.amount).toLocaleString(undefined, {minimumFractionDigits: 2}));
                setT('charge-pdc-amount', '₱' + Number(s.charge_pdc.amount).toLocaleString(undefined, {minimumFractionDigits: 2}));
                setT('payments-amount', '₱' + Number(s.payments.amount).toLocaleString(undefined, {minimumFractionDigits: 2}));
                setT('check-dues-amount', '₱' + Number(s.check_dues.amount).toLocaleString(undefined, {minimumFractionDigits: 2}));
                setT('payables-due-amount', '₱' + Number(s.payables_due.amount).toLocaleString(undefined, {minimumFractionDigits: 2}));
            }
        });
}

function updateConsolidatedSummary() {
    fetch('../api/spareparts_dashboard_api.php?action=get_consolidated_summary')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const s = data.summary;
                const setT = (id, val) => {
                    const el = document.getElementById(id);
                    if (el) el.textContent = val;
                };
                setT('global-cash-amount', '₱' + Number(s.cash.amount).toLocaleString(undefined, {minimumFractionDigits: 2}));
                setT('global-charge-amount', '₱' + Number(s.charge.amount).toLocaleString(undefined, {minimumFractionDigits: 2}));
                setT('global-charge-pdc-amount', '₱' + Number(s.charge_pdc.amount).toLocaleString(undefined, {minimumFractionDigits: 2}));
                setT('global-payments-amount', '₱' + Number(s.payments.amount).toLocaleString(undefined, {minimumFractionDigits: 2}));
                setT('global-total-amount', '₱' + Number(s.total.amount).toLocaleString(undefined, {minimumFractionDigits: 2}));
            }
        });
}

function updateGlobalInventoryStats() {
    fetch('../api/spareparts_dashboard_api.php?action=get_global_inventory_stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const s = data.stats;
                const setT = (id, val) => {
                    const el = document.getElementById(id);
                    if (el) el.textContent = val;
                };
                setT('total-inv-qty', Number(s.total_qty).toLocaleString());
                setT('total-inv-value', '₱' + Number(s.total_value).toLocaleString(undefined, {minimumFractionDigits: 2}));
                setT('total-monthly-sales', '₱' + Number(s.monthly_sales).toLocaleString(undefined, {minimumFractionDigits: 2}));
                setT('total-yearly-sales', '₱' + Number(s.yearly_sales).toLocaleString(undefined, {minimumFractionDigits: 2}));
            }
        });
}

function updateInventorySummary() {
    fetch('../api/spareparts_dashboard_api.php?action=get_inventory_summary')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const container = document.getElementById('inventory-summary-container');
                if (!container) return;
                
                let html = '';
                let totalQty = 0;
                let totalValue = 0;
                
                data.summary.forEach(item => {
                    const qty = Number(item.qty);
                    const val = Number(item.value);
                    totalQty += qty;
                    totalValue += val;
                    
                    html += `
                        <div class="summary-row">
                            <span class="summary-row-label text-truncate me-2" title="${item.brand || 'No Brand'}">${item.brand || 'No Brand'}</span>
                            <div class="text-end flex-shrink-0">
                                <div class="summary-row-val">${qty.toLocaleString()} Qty</div>
                                <div class="text-muted small" style="font-size: 0.7rem;">₱${val.toLocaleString(undefined, {minimumFractionDigits: 2})}</div>
                            </div>
                        </div>
                    `;
                });
                
                container.innerHTML = html || '<div class="text-center py-3 text-muted small">No stock available today.</div>';
                
                const qtyEl = document.getElementById('inv-total-qty');
                const valEl = document.getElementById('inv-total-value');
                if (qtyEl) qtyEl.textContent = totalQty.toLocaleString();
                if (valEl) valEl.textContent = '₱' + totalValue.toLocaleString(undefined, {minimumFractionDigits: 2});
            }
        });
}

function updateInventorySummaryByBranch() {
    fetch('../api/spareparts_dashboard_api.php?action=get_sales_summary_by_branch')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const container = document.getElementById('inventory-branch-summary-container');
                if (!container) return;
                
                let html = '';
                let totalQty = 0;
                let totalValue = 0;
                
                data.summary.forEach(item => {
                    const qty = Number(item.qty);
                    const val = Number(item.value);
                    totalQty += qty;
                    totalValue += val;
                    
                    html += `
                        <div class="summary-row">
                            <span class="summary-row-label text-truncate me-2">${item.branch}</span>
                            <div class="text-end flex-shrink-0">
                                <div class="summary-row-val">${qty.toLocaleString()} Sold</div>
                                <div class="text-muted small" style="font-size: 0.7rem;">₱${val.toLocaleString(undefined, {minimumFractionDigits: 2})}</div>
                            </div>
                        </div>
                    `;
                });
                
                container.innerHTML = html || '<div class="text-center py-3 text-muted small">No global sales available today.</div>';
                
                const qtyEl = document.getElementById('global-inv-total-qty');
                const valEl = document.getElementById('global-inv-total-value');
                if (qtyEl) qtyEl.textContent = totalQty.toLocaleString();
                if (valEl) valEl.textContent = '₱' + totalValue.toLocaleString(undefined, {minimumFractionDigits: 2});
            }
        });
}

async function updatePendingTransfers() {
    const container = document.getElementById('pending-transfers-container');
    const modalBody = document.getElementById('incoming-alert-modal-body');
    const modalEl = document.getElementById('incomingTransferAlertModal');
    
    try {
        const [transfersRes, returnsRes] = await Promise.all([
            fetch('../api/spareparts_dashboard_api.php?action=get_pending_transfers').then(r => r.json()),
            fetch('../api/spareparts_dashboard_api.php?action=get_return_alerts').then(r => r.json())
        ]);

        const hasTransfers = transfersRes.success && transfersRes.transfers.length > 0;
        const hasReturns = returnsRes.success && returnsRes.alerts.length > 0;

        if (hasTransfers || hasReturns) {
            if (container) container.style.display = 'none';

            if (modalBody) {
                let modalHtml = '<div class="list-group list-group-flush shadow-sm bg-light">';
                
                // Show Pending Transfers first
                if (hasTransfers) {
                    modalHtml += `<div class="p-3 bg-danger text-white small fw-bold text-uppercase ls-1"><i class="bi bi-box-arrow-in-down me-1"></i> Pending Incoming Transfers</div>`;
                    transfersRes.transfers.forEach(t => {
                        modalHtml += `
                            <div class="list-group-item p-4 border-bottom bg-white border-start border-4 border-danger">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="flex-grow-1">
                                        <div class="small fw-bold text-danger text-uppercase mb-1 ls-1" style="font-size: 0.65rem;"><i class="bi bi-geo-alt-fill me-1"></i>Origin Branch</div>
                                        <h5 class="fw-bold text-dark mb-1 font-sans">${t.from_branch}</h5>
                                        <div class="text-muted small">
                                            <i class="bi bi-calendar3 me-1"></i>Sent on ${t.transfer_date} 
                                            <span class="mx-2">|</span> 
                                            <i class="bi bi-box-seam me-1"></i>${t.item_count} Items
                                        </div>
                                    </div>
                                    <div class="ms-3 d-flex flex-column align-items-end gap-2">
                                        <span class="badge bg-danger-light text-danger border border-danger border-opacity-25 rounded-pill px-3 py-2 fw-bold" style="font-size: 0.65rem;">IN-TRANSIT</span>
                                        <button onclick="viewTransferDetailsUI(${t.id}, '${t.from_branch}')" class="btn btn-sm btn-dark fw-bold rounded-pill px-4 shadow-sm border-0 mt-2">
                                            <i class="bi bi-eye me-1"></i> View
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                }

                // Show Returns Alerts
                if (hasReturns) {
                    modalHtml += `<div class="p-3 bg-secondary text-white small fw-bold text-uppercase ls-1 mt-2"><i class="bi bi-arrow-return-right me-1"></i> Returned Items (Rejected)</div>`;
                    returnsRes.alerts.forEach(a => {
                        modalHtml += `
                            <div class="list-group-item p-4 border-bottom bg-white border-start border-4 border-secondary">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <span class="badge bg-secondary text-white rounded-pill">${a.quantity} Qty</span>
                                            <h6 class="fw-bold text-dark mb-0">${a.part_no}</h6>
                                        </div>
                                        <div class="text-muted small mb-2">${a.description}</div>
                                        <div class="bg-light p-2 rounded small border-start border-3 border-danger">
                                            <i class="bi bi-chat-left-dots-fill me-1 text-danger"></i> 
                                            <span class="fw-bold text-danger">REASON:</span> ${a.reason}
                                        </div>
                                    </div>
                                    <div class="ms-3 text-end">
                                        <div class="small fw-bold text-muted text-uppercase mb-1" style="font-size: 0.65rem;">Returned From</div>
                                        <div class="fw-bold text-dark mb-1 small">${a.sender}</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">${a.transaction_date}</div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                }

                modalHtml += '</div>';
                modalBody.innerHTML = modalHtml;

                const badge = document.getElementById('incoming-badge');
                if (badge) {
                    const totalCount = (transfersRes.transfers?.length || 0) + (returnsRes.alerts?.length || 0);
                    badge.innerText = totalCount;
                    badge.classList.toggle('d-none', totalCount === 0);
                }

                if (!window.hasShownPendingModal && typeof bootstrap !== 'undefined' && modalEl) {
                    try {
                        const m = bootstrap.Modal.getOrCreateInstance(modalEl);
                        if (m) {
                            m.show();
                            window.hasShownPendingModal = true;
                        }
                    } catch (e) {
                        console.warn("Bootstrap Modal show failed:", e);
                    }
                }
            }
        } else {
            const badge = document.getElementById('incoming-badge');
            if (badge) badge.classList.add('d-none');
            if (container) {
                container.innerHTML = '';
                container.style.display = 'none';
            }
            if (modalBody && !modalBody.innerHTML.includes('transfer-process-table')) {
                modalBody.innerHTML = `<div class="p-5 text-center text-muted">No pending transfers or return alerts.</div>`;
            }
        }
    } catch (err) {
        console.warn("Dashboard transfer check failed:", err);
    }
}

function viewTransferDetailsUI(transferId, fromBranch) {
    const modalBody = document.getElementById('incoming-alert-modal-body');
    const modalTitle = document.querySelector('#incomingTransferAlertModal .modal-title');
    
    if (modalTitle) {
        modalTitle.innerHTML = `<i class="bi bi-box-seam-fill me-2"></i>TRANSFER FROM: ${fromBranch}`;
    }
    
    modalBody.innerHTML = `
        <div class="p-5 text-center text-muted">
            <div class="spinner-border text-danger mb-3" role="status"></div>
            <p class="mb-0 fw-bold ls-1">Loading items...</p>
        </div>
    `;

    fetch(`../api/spareparts_dashboard_api.php?action=get_transfer_details&transfer_id=${transferId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                let html = `
                    <div class="p-4 bg-white">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="transfer-process-table">
                                <thead class="table-dark">
                                    <tr>
                                        <th class="text-center" style="width: 40px;">
                                            <input type="checkbox" class="form-check-input" id="check-all-items" checked onchange="toggleAllItems(this)">
                                        </th>
                                        <th>Part Details</th>
                                        <th class="text-center">Qty</th>
                                        <th>Return Reason (if unchecked)</th>
                                    </tr>
                                </thead>
                                <tbody>
                `;

                data.items.forEach(item => {
                    html += `
                        <tr data-item-id="${item.id}">
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input item-check" checked data-id="${item.id}" onchange="toggleReasonInput(this)">
                            </td>
                            <td>
                                <div class="fw-bold text-dark mb-0">${item.part_no}</div>
                                <div class="small text-muted">${item.description}</div>
                            </td>
                            <td class="text-center fw-bold">${item.quantity}</td>
                            <td>
                                <input type="text" class="form-control form-control-sm return-reason-input d-none" 
                                       placeholder="Enter reason for return..." 
                                       maxlength="255">
                            </td>
                        </tr>
                    `;
                });

                html += `
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4 d-flex justify-content-between align-items-center">
                            <button onclick="updatePendingTransfers()" class="btn btn-outline-secondary rounded-pill px-4 fw-bold">
                                <i class="bi bi-arrow-left me-1"></i> Back
                            </button>
                            <div class="d-flex gap-2">
                                <button onclick="printTransferSummaryUI(${transferId}, '${fromBranch}')" class="btn btn-outline-dark rounded-pill px-4 fw-bold">
                                    <i class="bi bi-printer me-1"></i> Print Summary
                                </button>
                                <button onclick="submitPartialProcessing(${transferId})" class="btn btn-danger rounded-pill px-5 fw-bold shadow">
                                    <i class="bi bi-check2-circle me-1"></i> Process Transfer
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                modalBody.innerHTML = html;
            } else {
                modalBody.innerHTML = `<div class="alert alert-danger m-4">${data.message}</div>`;
            }
        });
}

function toggleAllItems(master) {
    const checks = document.querySelectorAll('.item-check');
    checks.forEach(c => {
        c.checked = master.checked;
        toggleReasonInput(c);
    });
}

function toggleReasonInput(checkbox) {
    const row = checkbox.closest('tr');
    const reasonInput = row.querySelector('.return-reason-input');
    if (checkbox.checked) {
        reasonInput.classList.add('d-none');
        reasonInput.value = '';
    } else {
        reasonInput.classList.remove('d-none');
        reasonInput.focus();
    }
}

function submitPartialProcessing(transferId) {
    const acceptedIds = [];
    const returnedItems = [];
    let allValid = true;

    document.querySelectorAll('#transfer-process-table tbody tr').forEach(row => {
        const check = row.querySelector('.item-check');
        const id = check.dataset.id;
        
        if (check.checked) {
            acceptedIds.push(id);
        } else {
            const reason = row.querySelector('.return-reason-input').value.trim();
            if (!reason) {
                allValid = false;
                row.querySelector('.return-reason-input').classList.add('is-invalid');
            } else {
                row.querySelector('.return-reason-input').classList.remove('is-invalid');
                returnedItems.push({ id: id, reason: reason });
            }
        }
    });

    if (!allValid) {
        if (typeof Swal !== 'undefined') {
            Swal.fire('Required', 'Please provide a reason for all items being returned.', 'warning');
        } else {
            alert('Please provide a reason for all items being returned.');
        }
        return;
    }

    if (acceptedIds.length === 0 && returnedItems.length === 0) return;

    const formData = new FormData();
    formData.append('transfer_id', transferId);
    formData.append('accepted_ids', JSON.stringify(acceptedIds));
    formData.append('returned_items', JSON.stringify(returnedItems));

    fetch('../api/spareparts_dashboard_api.php?action=process_partial_transfer', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Close selection modal first
            const modalEl = document.getElementById('incomingTransferAlertModal');
            const m = bootstrap.Modal.getInstance(modalEl);
            if (m) m.hide();

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Transfer Success!',
                    text: data.message + ' What would you like to do next?',
                    icon: 'success',
                    showCancelButton: true,
                    confirmButtonText: '<i class="bi bi-printer me-2"></i>Preview & Print',
                    cancelButtonText: 'Done',
                    confirmButtonColor: '#004d40',
                    cancelButtonColor: '#6c757d',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (typeof printTransferSummaryUI === 'function') {
                            printTransferSummaryUI(data.transfer_id, data.from_branch);
                        }
                    }
                    updatePendingTransfers();
                    if (window.location.href.includes('transfer_stock.php')) {
                        location.reload(); 
                    }
                });
            } else {
                alert(data.message);
                updatePendingTransfers();
                if (window.location.href.includes('transfer_stock.php')) {
                    location.reload(); 
                }
            }
        } else {
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', data.message, 'error');
            } else {
                alert(data.message);
            }
        }
    });
}

function printTransferSummaryUI(transferId, fromBranch) {
    const currentBranch = window.currentBranch || window.userBranch || 'Current Branch';
    const isOutgoing = (fromBranch === currentBranch);
    const typeLabel = isOutgoing ? 'Stock Transfer (Outgoing)' : 'Stock Transfer (Incoming)';

    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Transfer Summary - #${transferId}</title>
            <style>
                body { font-family: 'Segoe UI', Arial, sans-serif; padding: 40px; color: #333; }
                .header { text-align: center; border-bottom: 2px solid #004d40; padding-bottom: 15px; margin-bottom: 30px; }
                .header h1 { margin: 0; color: #004d40; font-size: 24px; }
                .header p { margin: 5px 0; color: #666; font-size: 14px; }
                .meta { display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 14px; }
                .meta b { color: #004d40; }
                table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                th { background: #f4f4f4; text-align: left; padding: 12px; border-bottom: 2px solid #ddd; font-size: 13px; text-transform: uppercase; }
                td { padding: 12px; border-bottom: 1px solid #eee; font-size: 13px; }
                .footer { margin-top: 50px; text-align: right; font-size: 12px; color: #999; }
                @print { .no-print { display: none; } }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>ROXAS CITY SOLID MERCHANDISING</h1>
                <p>Transfer Summary Document - Spare Parts Management</p>
            </div>
            
            <div class="meta">
                <div>
                    <div><b>Transfer ID:</b> #${transferId}</div>
                    <div id="origin-info"><b>Origin:</b> ${fromBranch}</div>
                    <div id="dest-info"><b>Destination:</b> ${currentBranch}</div>
                </div>
                <div style="text-align: right;">
                    <div><b>Print Date:</b> ${new Date().toLocaleString()}</div>
                    <div><b>Type:</b> ${typeLabel}</div>
                </div>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Part Number</th>
                        <th>Description</th>
                        <th style="text-align: center;">Qty Transferred</th>
                    </tr>
                </thead>
                <tbody id="print-tbody">
                    <tr><td colspan="3" style="text-align: center;">Loading items...</td></tr>
                </tbody>
            </table>
            
            <div class="footer">
                <p>Generated by Spare Parts Management System &copy; ${new Date().getFullYear()}</p>
            </div>

            <script>
                fetch('../api/spareparts_inventory.php?action=get_transfer_details&id=${transferId}')
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            const data = res.data;
                            const tbody = document.getElementById('print-tbody');
                            let html = '';
                            data.items.forEach(item => {
                                html += \`
                                    <tr>
                                        <td>\${item.part_no}</td>
                                        <td>\${item.description}</td>
                                        <td style="text-align: center; font-weight: bold;">\${item.quantity}</td>
                                    </tr>
                                \`;
                            });
                            tbody.innerHTML = html;
                            
                            document.getElementById('origin-info').innerHTML = '<b>Origin:</b> ' + data.from_branch;
                            document.getElementById('dest-info').innerHTML = '<b>Destination:</b> ' + data.to_branch;
                            
                            setTimeout(() => { window.print(); }, 500);
                        }
                    });
            </script>
        </body>
        </html>
    `);
    printWindow.document.close();
}
