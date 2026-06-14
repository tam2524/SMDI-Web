</div>
</div>
</div>

</div> <!-- End of .tab-content -->
</div>
</div>
</main>

<div class='modal fade' id='addPartsInModal' tabindex='-1' aria-hidden='true'>
    <div class='modal-dialog modal-xl modal-dialog-scrollable'>
        <form id='addPartsInForm' class='modal-content'>
            <div class='modal-header bg-dark text-white'>
                <h5 class='modal-title text-white'><i class="bi bi-box-arrow-in-down me-2"></i>Record Incoming Stock
                </h5>
                <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
            </div>
            <div class='modal-body p-4'>
                <!-- Section A: Stock Info -->
                <div class="row g-3 mb-4 pb-3 border-bottom align-items-end">
                    <div class="col-sm-6 col-md-4">
                        <label class='form-label small fw-bold text-muted mb-1 text-nowrap'><i
                                class="bi bi-calendar-date me-1"></i>Date Received *</label>
                        <input type='date' class='form-control form-control-sm' id='add_date' name='date' required>
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <label class='form-label small fw-bold text-muted mb-1 text-nowrap'><i
                                class="bi bi-hash me-1"></i>Invoice Number</label>
                        <input type='text' class='form-control form-control-sm' id='add_invoice_no' name='invoice_no'
                            placeholder="Enter Invoice #">
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <label class='form-label small fw-bold text-muted mb-1 text-nowrap'><i
                                class="bi bi-hash me-1"></i>Reference No. (Optional)</label>
                        <input type='text' class='form-control form-control-sm' id='add_reference' name='reference'
                            placeholder="Optional">
                    </div>
                </div>

                <!-- Section B: Add Items -->
                <ul class="nav nav-tabs nav-fill mb-3" id="addPartsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold" id="search-existing-tab" data-bs-toggle="tab"
                            data-bs-target="#search-existing" type="button" role="tab"><i
                                class="bi bi-search me-2"></i>Search Existing</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold" id="manual-entry-tab" data-bs-toggle="tab"
                            data-bs-target="#manual-entry" type="button" role="tab"><i
                                class="bi bi-pencil-square me-2"></i>Manual Entry</button>
                    </li>
                </ul>

                <div class="tab-content mb-4" id="addPartsTabsContent">
                    <div class="tab-pane fade show active" id="search-existing" role="tabpanel">
                        <div class="position-relative">
                            <input type="text" id="addPartSearchInput" class="form-control form-control-lg"
                                placeholder="🔍 Search by Part No. or Part Name...">
                            <div id="addPartSearchResults"
                                class="list-group search-results-list d-none shadow-sm rounded-bottom"></div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="manual-entry" role="tabpanel">
                        <div class="card bg-light border-0">
                            <div class="card-body p-3">
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <label class="form-label small mb-1 fw-bold text-muted">Brand *</label>
                                        <input type="text" id="manual_brand" class="form-control form-control-sm"
                                            placeholder="e.g. Honda">
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label small mb-1 fw-bold text-muted">Part No
                                            *</label>
                                        <input type="text" id="manual_part_no" class="form-control form-control-sm"
                                            placeholder="Required">
                                    </div>
                                    <div class="col-12 mt-2">
                                        <label class="form-label small mb-1 fw-bold text-muted">Part Name
                                            *</label>
                                        <input type="text" id="manual_desc" class="form-control form-control-sm"
                                            placeholder="Required">
                                    </div>
                                    <div class="col-md-3 mt-2">
                                        <label class="form-label small mb-1 fw-bold text-muted">Qty *</label>
                                        <input type="number" id="manual_qty" class="form-control form-control-sm"
                                            value="1" min="1">
                                    </div>
                                    <div class="col-md-3 mt-2">
                                        <label class="form-label small mb-1 fw-bold text-muted">Cost</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" id="manual_cost" class="form-control" step="0.01"
                                                placeholder="0.00">
                                        </div>
                                    </div>
                                    <div class="col-md-3 mt-2">
                                        <label class="form-label small mb-1 fw-bold text-muted">Sell
                                            Price</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" id="manual_price" class="form-control" step="0.01"
                                                placeholder="0.00">
                                        </div>
                                    </div>
                                    <div class="col-md-3 mt-2 d-flex align-items-end">
                                        <button type="button" class="btn btn-primary btn-sm w-100 fw-bold text-white"
                                            id="addManualPartBtn" title="Press Enter to Add">
                                            <i class="bi bi-plus-circle me-1"></i> Add Item
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Items to Add Table -->
                <h6 class="border-bottom pb-2 fw-bold text-dark d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-list-ul me-2"></i>Items to Add</span>
                    <span class="badge bg-secondary rounded-pill" id="itemCountBadge">0 Items</span>
                </h6>
                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-bordered table-sm table-hover mb-0">
                        <thead class="table-light text-muted" style="position: sticky; top: 0; z-index: 1;">
                            <tr>
                                <th class="text-center">#</th>
                                <th>Brand</th>
                                <th>Part Details</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Cost</th>
                                <th class="text-end">Sell Price</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="partsToAddList">
                            <tr id="emptyAddListRow">
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="bi bi-box-seam display-4 d-block mb-2 text-secondary opacity-50"></i>
                                    <h6 class="fw-bold mb-1">No items yet</h6>
                                    <p class="small mb-0">Start by searching or adding manually</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Summary Bar -->
                <div class="mt-3 p-3 rounded" style="background-color: #ebf5f3; border: 1px solid #c8e6c9;">
                    <div class="row text-center fw-bold" style="color: var(--smdi-green-dark);">
                        <div class="col-4 border-end border-success border-opacity-25">
                            <span class="d-block small text-muted text-uppercase mb-1">Total Items</span>
                            <span class="fs-5" id="addTotalQty">0</span>
                        </div>
                        <div class="col-4 border-end border-success border-opacity-25">
                            <span class="d-block small text-muted text-uppercase mb-1">Total Cost</span>
                            <span class="fs-5 text-danger" id="addTotalCost">₱0.00</span>
                        </div>
                        <div class="col-4">
                            <span class="d-block small text-muted text-uppercase mb-1">Total Value</span>
                            <span class="fs-5 text-success" id="addTotalPrice">₱0.00</span>
                        </div>
                    </div>
                </div>

            </div>
            <div class='modal-footer bg-light border-top-0 flex-shrink-0'>
                <button type='button' class='btn btn-outline-secondary px-4' data-bs-dismiss='modal'>Cancel</button>
                <button type='submit' class='btn btn-primary px-4 fw-bold text-white'>Save Stock (IN)</button>
            </div>
        </form>
    </div>
</div>
<div class='modal fade' id='editPartModal' tabindex='-1' aria-hidden='true'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <form id='editPartForm' enctype="multipart/form-data">
                <div class='modal-header bg-dark text-white'>
                    <h5 class='modal-title text-white'><i class="bi bi-pencil-square me-2 "></i>Edit Part Details
                    </h5>
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                </div>
                <div class='modal-body p-4'>
                    <input type="hidden" name="id" id="edit_part_id">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Branch</label>
                            <input type="text" class="form-control bg-light" name="branch" id="edit_branch" readonly required>
                            <small class="text-danger fw-bold d-block mt-1" style="font-size: 0.75em;"><i class="bi bi-exclamation-circle-fill me-1"></i>To change branch, please use the transfer feature.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Brand</label>
                            <input type="text" class="form-control" name="brand" id="edit_brand">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label">Part Number</label>
                            <input type="text" class="form-control" name="part_no" id="edit_part_no" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Stock</label>
                            <input type="number" class="form-control" name="stock" id="edit_stock" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Part Name</label>
                        <textarea class="form-control" name="description" id="edit_description" rows="2"
                            required></textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">Unit Cost</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" step="0.01" class="form-control" name="cost" id="edit_cost"
                                    required>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Selling Price</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" step="0.01" class="form-control" name="price" id="edit_price"
                                    required>
                            </div>
                        </div>
                        <div class="col-12 mt-2">
                            <label class="form-label">Thumbnail Image (Optional)</label>
                            <input type="file" class="form-control" name="part_image" id="edit_part_image"
                                accept="image/*">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Minimum Stock (Low Stock Alert)</label>
                            <input type="number" class="form-control" name="min_stock" id="edit_min_stock" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Invoice Number</label>
                            <input type="text" class="form-control" name="invoice_no" id="edit_invoice_no"
                                placeholder="Optional">
                        </div>
                        <div class="col-md-12 mt-2">
                            <label class="form-label">Warehouse Bin Location</label>
                            <input type="text" class="form-control" name="bin_location" id="edit_bin_location"
                                placeholder="e.g. Row 2, Bin C">
                        </div>
                        <div class="col-12 mt-3" id="edit_reason_container">
                            <label class="form-label fw-bold text-danger">Reason for Adjustment</label>
                            <textarea class="form-control border-danger" name="change_reason" id="edit_change_reason"
                                rows="2" placeholder="Required if changing quantity..."></textarea>
                            <small class="text-muted">Please provide a reason if you are manually updating the stock
                                quantity.</small>
                        </div>
                    </div>
                </div>
                <div class='modal-footer bg-light'>
                    <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>
                    <button type='submit' class='btn btn-dark px-4 text-white'>Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class='modal fade' id='sellPartsOutModal' tabindex='-1' aria-labelledby='sellPartsOutModalLabel'
    aria-hidden='true'>
    <div class='modal-dialog modal-lg'>
        <div class='modal-content'>
            <form id='sellPartsOutForm'>
                <div class='modal-header bg-dark text-white'>
                    <h5 class='modal-title text-white' id='sellPartsOutModalLabel'><i
                            class="bi bi-cart-plus me-2"></i>Record
                        New Sale</h5><button type='button' class='btn-close btn-close-white'
                        data-bs-dismiss='modal'></button>
                </div>
                <div class='modal-body p-4'>
                    <div class="row g-3 mb-2">
                        <div class="col-md-6">
                            <label for='out_or_number' class='form-label'>Official Receipt No.</label>
                            <input type='text' class='form-control' id='out_or_number' required autocomplete="off">
                            <div id="or_availability_feedback" class="small mt-1"></div>
                        </div>
                        <div class="col-md-6 position-relative">
                            <label for='out_customer_name' class='form-label'>Customer Name</label>
                            <input type='text' class='form-control' id='out_customer_name' required autocomplete="off">
                            <div id="saleCustomerSearchResults" class="list-group border rounded shadow-sm mt-1"
                                style="max-height: 200px; overflow-y: auto; position: absolute; width: calc(100% - 1.5rem); z-index: 1052; display: none; background: white;">
                            </div>
                        </div>
                        <div class="col-md-6"><label for='out_date' class='form-label'>Date of Sale</label><input
                                type='date' class='form-control' id='out_date' required></div>
                        <div class="col-md-6"><label for='out_transaction_type' class='form-label'>Payment
                                Type</label><select class='form-select' id='out_transaction_type' required>
                                <option value='cash' selected>Cash Sales</option>
                                <option value='charge'>Charge Sales (Installment)</option>
                            </select></div>
                    </div>
                    <div class="mb-3 position-relative">
                        <label class="form-label fw-bold small text-muted text-uppercase">Search Product</label>
                        <input type="text" id="salePartSearchInput" class="form-control"
                            placeholder="Enter Part No. or Part Name to add items..." autocomplete="off">
                        <div id="salePartSearchResults" class="list-group border rounded shadow-sm mt-1"
                            style="max-height: 200px; overflow-y: auto; position: absolute; width: 100%; z-index: 1051; display: none; background: white;">
                        </div>
                    </div>
                    <h6 class="mb-2">Sale Items</h6>
                    <div class="table-responsive border rounded">
                        <table class="table table-striped table-sm mb-0">
                            <thead class="bg-dark text-white">
                                <tr>
                                    <th>Item</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="partsForSaleList">
                                <tr id="emptySaleListRow">
                                    <td colspan="5" class="text-center text-muted p-4">Cart is empty</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <div style="width: 320px;">
                            <hr>
                            <div class="d-flex justify-content-between fw-bold fs-5 text-dark"><span>Grand
                                    Total:</span><span id="pos-grand-total">₱0.00</span></div>
                        </div>
                    </div>
                </div>
                <div class='modal-footer'><button type='button' class='btn btn-secondary'
                        data-bs-dismiss='modal'>Cancel</button><button type='submit' class='btn btn-dark'><i
                            class="bi bi-check-circle me-2"></i>Confirm Sale</button></div>
            </form>
        </div>
    </div>
</div>

<div class='modal fade' id='viewSaleDetailsModal' tabindex='-1' aria-hidden='true'>
    <div class='modal-dialog modal-lg'>
        <div class='modal-content'>
            <div class='modal-header bg-dark text-white'>
                <h5 class='modal-title text-white'><i class="bi bi-eye me-2"></i>Sale Details</h5>
                <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
            </div>
            <div class='modal-body p-4' id="saleDetailsContent">
                <div class="text-center p-5">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2">Loading sale details...</p>
                </div>
            </div>
            <div class='modal-footer'>
                <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>
            </div>
        </div>
    </div>
</div>

<div class='modal fade' id='recordPaymentModal' tabindex='-1' aria-labelledby='recordPaymentModalLabel'
    aria-hidden='true'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <form id='recordPaymentForm'>
                <div class='modal-header bg-dark text-white'>
                    <h5 class='modal-title text-white' id='recordPaymentModalLabel'><i
                            class="bi bi-cash-coin me-2"></i>Record
                        Payment</h5><button type='button' class='btn-close btn-close-white'
                        data-bs-dismiss='modal'></button>
                </div>
                <div class='modal-body p-5'>
                    <div class='mb-3'><label for='payment_customer_search' class='form-label fw-bold'>Search
                            Customer with
                            Balance</label><input type='text' class='form-control' id='payment_customer_search'
                            placeholder="Type customer name..." autocomplete="off">
                        <div id="customerSearchResults" class="list-group mt-1 shadow-sm"></div>
                    </div>
                    <input type="hidden" id="payment_or_number" name="or_number">
                    <input type="hidden" id="payment_customer_name" name="customer_name">
                    <input type="hidden" id="payment_branch" name="branch">
                    <div class='mb-3'>
                        <label for='payment_receipt_no' class='form-label fw-bold text-success'>Payment Receipt
                            Number</label>
                        <input type='text' class='form-control border-success' id='payment_receipt_no'
                            name="payment_receipt_no" required placeholder="Enter the Receipt # issued">
                        <small id="balance_info" class="form-text text-muted d-block mt-2"></small>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for='payment_date' class='form-label'>Payment Date</label>
                            <input type='date' class='form-control' id='payment_date' name="payment_date" required>
                        </div>
                        <div class="col-md-4">
                            <label for='payment_amount' class='form-label'>Amount Paid</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type='number' step='0.01' class='form-control' id='payment_amount' name="amount"
                                    min='0' required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for='payment_method' class='form-label'>Method</label>
                            <select class='form-select' id='payment_method' name="payment_method" required>
                                <option value="Cash" selected>Cash</option>
                                <option value="Check">Check</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                            </select>
                        </div>
                    </div>

                    <!-- Extra Payment Details -->
                    <div id="payment_details_container" class="mt-3 d-none">
                        <div class="row g-3">
                            <div class="col-md-6 payment-detail-field check-field d-none">
                                <label class="form-label fw-bold">Check Number</label>
                                <input type="text" class="form-control" name="check_number" id="payment_check_number">
                            </div>
                            <div class="col-md-6 payment-detail-field bank-transfer-field d-none">
                                <label class="form-label fw-bold">Reference Number</label>
                                <input type="text" class="form-control" name="reference_number"
                                    id="payment_reference_number">
                            </div>
                            <div class="col-md-6 payment-detail-field check-field bank-transfer-field d-none">
                                <label class="form-label fw-bold">Bank Name</label>
                                <input type="text" class="form-control" name="bank_name" id="payment_bank_name">
                            </div>
                        </div>
                    </div>
                </div>
                <div class='modal-footer'><button type='button' class='btn btn-secondary '
                        data-bs-dismiss='modal'>Close</button><button type='submit' class='btn btn-dark text-white'><i
                            class=" text-white bi bi-check-lg me-2"></i>Record
                        Payment</button></div>
            </form>
        </div>
    </div>
</div>
<div class='modal fade' id='editPaymentModal' tabindex='-1' aria-hidden='true'>
    <div class='modal-dialog modal-dialog-centered'>
        <div class='modal-content border-0 shadow-lg'>
            <form id="editPaymentForm">
                <div class='modal-header bg-warning text-dark border-0'>
                    <h5 class='modal-title fw-bold'><i class="bi bi-pencil-square me-2"></i>Edit Payment</h5>
                    <button type='button' class='btn-close' data-bs-dismiss='modal'></button>
                </div>
                <div class='modal-body p-5'>
                    <input type="hidden" id="edit_payment_id" name="payment_id">

                    <div class='mb-3'>
                        <label class='form-label fw-bold'>Customer Name</label>
                        <input type='text' class='form-control-plaintext fw-bold' id='edit_payment_customer' readonly>
                    </div>

                    <div class='mb-3'>
                        <label for='edit_payment_receipt_no' class='form-label fw-bold text-success'>Payment Receipt
                            Number</label>
                        <input type='text' class='form-control border-success' id='edit_payment_receipt_no'
                            name="payment_receipt_no" required>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for='edit_payment_date' class='form-label'>Payment Date</label>
                            <input type='date' class='form-control' id='edit_payment_date' name="payment_date" required>
                        </div>
                        <div class="col-md-4">
                            <label for='edit_payment_amount' class='form-label'>Amount Paid</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type='number' step='0.01' class='form-control' id='edit_payment_amount'
                                    name="amount" min='0' required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for='edit_payment_method' class='form-label'>Method</label>
                            <select class='form-select' id='edit_payment_method' name="payment_method" required>
                                <option value="Cash">Cash</option>
                                <option value="Check">Check</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                            </select>
                        </div>
                    </div>

                    <!-- Extra Payment Details -->
                    <div id="edit_payment_details_container" class="mt-3 d-none">
                        <div class="row g-3">
                            <div class="col-md-6 edit-payment-detail-field edit-check-field d-none">
                                <label class="form-label fw-bold">Check Number</label>
                                <input type="text" class="form-control" name="check_number"
                                    id="edit_payment_check_number">
                            </div>
                            <div class="col-md-6 edit-payment-detail-field edit-bank-transfer-field d-none">
                                <label class="form-label fw-bold">Reference Number</label>
                                <input type="text" class="form-control" name="reference_number"
                                    id="edit_payment_reference_number">
                            </div>
                            <div
                                class="col-md-6 edit-payment-detail-field edit-check-field edit-bank-transfer-field d-none">
                                <label class="form-label fw-bold">Bank Name</label>
                                <input type="text" class="form-control" name="bank_name" id="edit_payment_bank_name">
                            </div>
                        </div>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                    <button type='submit' class='btn btn-warning text-dark fw-bold'><i class="bi bi-save me-2"></i>Save
                        Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class='modal fade' id='transferPartsModal' tabindex='-1' aria-hidden='true'>
    <div class='modal-dialog modal-lg modal-dialog-scrollable'>
        <form id='transferPartsForm' class='modal-content border-0 shadow-lg'>
            <div class='modal-header bg-dark-green text-white border-0'>
                <h5 class='modal-title fw-bold text-white'><i class="bi bi-arrow-left-right me-2"></i>Transfer Spare
                    Parts</h5>
                <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
            </div>
            <div class='modal-body p-4'>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class='form-label fw-bold small text-muted text-uppercase'>Transfer Date</label>
                        <input type='date' class='form-control border-0 bg-light fw-bold' id='transfer_date'
                            name='transfer_date' required>
                    </div>
                    <div class="col-md-6">
                        <label class='form-label fw-bold small text-muted text-uppercase'>Destination Branch</label>
                        <select class='form-select border-0 bg-light fw-bold' id='to_branch' name='to_branch' required>
                            <option value="" disabled selected>Select destination...</option>
                            <!-- Populated dynamically -->
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold small text-muted text-uppercase">Search Part to
                        Transfer</label>
                    <input type="text" id="transferPartSearchInput" class="form-control"
                        placeholder="Enter Part No. or Part Name to add items..." autocomplete="off">
                    <div id="transferPartSearchResults" class="list-group border rounded shadow-sm mt-1"
                        style="max-height: 200px; overflow-y: auto; position: absolute; width: 95%; z-index: 1051; display: none;">
                    </div>
                </div>

                <h6 class="fw-bold mb-3 small text-muted text-uppercase text-dark-green"><i
                        class="bi bi-list-ul me-2"></i>Items to Transfer</h6>
                <div class="table-responsive border rounded">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="bg-dark-green text-white">
                            <tr>
                                <th class="ps-3 py-2 text-white">Part Details</th>
                                <th class="text-center py-2 text-white" style="width: 120px;">Qty</th>
                                <th class="text-center py-2 text-white" style="width: 150px;">Cost (ea)</th>
                                <th class="text-center py-2 text-white" style="width: 60px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="partsToTransferList">
                            <tr id="emptyTransferListRow">
                                <td colspan="4" class="text-center text-muted p-5">
                                    <i class="bi bi-search d-block fs-3 mb-2 opacity-25"></i>
                                    Use the search bar above to add items
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class='modal-footer border-0 p-4 pt-0'>
                <button type='button' class='btn btn-outline-dark-green fw-bold px-4'
                    data-bs-dismiss='modal'>Cancel</button>
                <button type='submit' class='btn btn-dark fw-bold px-4'>Initiate Transfer</button>
            </div>
        </form>
    </div>
</div>

<div class='modal fade' id='editSaleModal' tabindex='-1' aria-hidden='true'>
    <div class='modal-dialog modal-dialog-centered'>
        <div class='modal-content border-0 shadow'>
            <form id='editSaleForm'>
                <div class='modal-header bg-dark text-white py-3'>
                    <h5 class='modal-title fw-bold'><i class="bi bi-pencil-square me-2"></i>Edit Sale </h5>
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                </div>
                <div class='modal-body p-4'>
                    <div class="alert alert-info border-0 shadow-sm small py-2 mb-4">
                        <i class="bi bi-info-circle-fill me-2"></i>Adjust transaction metadata below. Financial
                        adjustments will update aging balances.
                    </div>
                    <input type="hidden" name="original_or" id="edit_sale_original_or">
                    <input type="hidden" name="original_branch" id="edit_sale_original_branch">

                    <div class='row g-3'>
                        <div class='col-md-6 text-center border-end'>
                            <div class="p-3 bg-light rounded-3">
                                <div class='x-small fw-bold text-muted text-uppercase mb-1'>Current ID</div>
                                <div class='fs-4 fw-bold text-primary font-monospace' id='edit_sale_or_display'>-
                                </div>
                                <div class="small text-muted" id="edit_sale_branch_display">-</div>
                            </div>
                        </div>
                        <div class='col-md-6'>
                            <label class='form-label x-small fw-bold text-muted text-uppercase mb-1'>New OR
                                Number</label>
                            <div class="input-group mb-3">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-hash"></i></span>
                                <input type='text' class='form-control border-start-0 ps-0 font-monospace'
                                    id='edit_sale_or' name="new_or_number" required>
                            </div>
                            <label class='form-label x-small fw-bold text-muted text-uppercase mb-1'>Selling
                                Branch</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i
                                        class="bi bi-geo-alt"></i></span>
                                <select class='form-select border-start-0 ps-0' id='edit_sale_branch'
                                    name="from_location" required>
                                    <!-- Populated via JS -->
                                </select>
                            </div>
                        </div>

                        <hr class="my-2 opacity-10">

                        <div class='col-12'>
                            <label class='form-label x-small fw-bold text-muted text-uppercase mb-1'>Customer Full
                                Name</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-person"></i></span>
                                <input type='text' class='form-control border-start-0 ps-0 fw-bold'
                                    id='edit_sale_customer' name="customer_name" required>
                            </div>
                        </div>

                        <div class='col-md-6'>
                            <label class='form-label x-small fw-bold text-muted text-uppercase mb-1'>Transaction
                                Date</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i
                                        class="bi bi-calendar3"></i></span>
                                <input type='date' class='form-control border-start-0 ps-0' id='edit_sale_date'
                                    name="sale_date" required>
                            </div>
                        </div>

                        <div class='col-md-6'>
                            <label class='form-label x-small fw-bold text-muted text-uppercase mb-1'>Transaction
                                Type</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i
                                        class="bi bi-credit-card-2-front"></i></span>
                                <select class='form-select border-start-0 ps-0' id='edit_sale_type'
                                    name="transaction_type" required>
                                    <option value="cash">Cash Sales</option>
                                    <option value="charge">Charge Sales (Installment)</option>
                                </select>
                            </div>
                        </div>

                        <div class='col-12'>
                            <label class='form-label x-small fw-bold text-muted text-uppercase mb-1'>Total Sale
                                Amount (₱)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 fw-bold text-muted">₱</span>
                                <input type='number' step="0.01" class='form-control border-start-0 ps-0 fs-5 fw-bold'
                                    id='edit_sale_amount' name="total_amount" required>
                            </div>
                            <small class="text-muted">Adjusting this updates charge balances.</small>
                        </div>

                        <div class='col-12 mt-3'>
                            <label class='form-label x-small fw-bold text-danger text-uppercase mb-1'>Reason for
                                Revision</label>
                            <textarea class='form-control border-danger' id='edit_sale_reason' name="reason" required
                                rows="2" placeholder="Mandatory: Why are you editing this sale?"></textarea>
                            <small class="text-muted">Audit logging is mandatory for metadata revisions.</small>
                        </div>
                    </div>
                </div>
                <div class='modal-footer bg-light py-3 border-top-0'>
                    <button type='button' class='btn btn-outline-secondary px-4' data-bs-dismiss='modal'>Cancel</button>
                    <button type='submit' class='btn btn-dark px-4'><i class="bi bi-check2-circle me-1"></i>Save
                        Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>


<div class='modal fade' id='viewHistoryModal' tabindex='-1' aria-hidden='true'>
    <div class='modal-dialog modal-lg modal-dialog-centered'>
        <div class='modal-content border-0'>
            <div class='modal-header bg-success text-white'>
                <h5 class='modal-title text-white fw-bold'>Spare Parts Movement History</h5>
                <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
            </div>
            <div class='modal-body p-4'>
                <div class="mb-3">
                    <h5 class="fw-bold mb-1 text-success" id="historyPartDescription">Yamaha MIOAEROXD541</h5>
                    <div class="text-muted small">
                        Brand: <span id="historyPartBrand"></span> | Part #: <span id="historyPartNumber"></span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" style="border: 1px solid #e0e0e0;">
                        <thead class="bg-light text-success">
                            <tr>
                                <th class="py-2">Date</th>
                                <th class="py-2">Event</th>
                                <th class="py-2 text-center">Qty</th>
                                <th class="py-2">From</th>
                                <th class="py-2">To</th>
                                <th class="py-2">Status</th>
                                <th class="py-2">Invoice #</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody"></tbody>
                    </table>
                </div>
            </div>
            <div class='modal-footer border-0 p-4 pt-0'>
                <button type='button' class='btn btn-success text-white fw-bold px-4'
                    data-bs-dismiss='modal'>Close</button>
            </div>
        </div>
    </div>
</div>

<div class='modal fade' id='receiptModal' tabindex='-1'>
    <div class='modal-dialog modal-lg'>
        <div class='modal-content'>
            <div class='modal-header bg-dark text-white'>
                <h5 class='modal-title text-white' id="receiptTitle"></h5><button type='button'
                    class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
            </div>
            <div class='modal-body p-0' id='receiptBody'></div>
            <div class='modal-footer'><button type='button' class='btn btn-secondary no-print'
                    data-bs-dismiss='modal'>Close</button><button type='button' class='btn btn-outline-dark no-print'
                    id="downloadPdfBtn"><i class="bi bi-file-pdf me-2"></i>Download PDF</button><button type='button'
                    class='btn btn-dark no-print' onclick="window.print();"><i
                        class="bi bi-printer me-2"></i>Print</button></div>
        </div>
    </div>
</div>
<!-- Removed duplicate viewIncomingTransferModal -->

<div class='modal fade' id='successModal' tabindex='-1'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header bg-success text-white'>
                <h5 class='modal-title'>Success</h5><button type='button' class='btn-close btn-close-white'
                    data-bs-dismiss='modal'></button>
            </div>
            <div class='modal-body'>
                <p id='successMessage'></p>
            </div>
            <div class='modal-footer'><button type='button' class='btn btn-success' data-bs-dismiss='modal'>OK</button>
            </div>
        </div>
    </div>
</div>
<div class='modal fade' id='errorModal' tabindex='-1'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header bg-danger text-white'>
                <h5 class='modal-title'>Error</h5><button type='button' class='btn-close btn-close-white'
                    data-bs-dismiss='modal'></button>
            </div>
            <div class='modal-body'>
                <p id='errorMessage'></p>
            </div>
            <div class='modal-footer'><button type='button' class='btn btn-danger' data-bs-dismiss='modal'>OK</button>
            </div>
        </div>
    </div>
</div>


<div class='modal fade' id='salesPreviewModal' tabindex='-1' aria-hidden='true'>
    <div class='modal-dialog modal-xl'>
        <div class='modal-content'>
            <div class='modal-header bg-dark text-white'>
                <h5 class='modal-title text-white'><i class="bi bi-eye me-2"></i>Sales Print/Export Preview</h5>
                <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
            </div>
            <div class='modal-body p-4'>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h6 class="fw-bold mb-1">Refine Sales Preview List</h6>
                        <p class="small text-muted mb-0">Review the records below before finalized printing or
                            exporting.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <input type="text" id="salesPreviewSearch" class="form-control form-control-sm"
                            placeholder="Filter sales..." style="width: 200px;">
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                                data-bs-toggle="dropdown">
                                <i class="bi bi-layout-three-columns me-1"></i>Columns
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end p-3" id="salesPreviewColumnToggles"
                                style="min-width: 200px;">
                                <li>
                                    <div class="form-check"><input class="form-check-input sales-col-toggle"
                                            type="checkbox" value="0" checked id="scol0"><label </li>
                                <li>
                                    <div class="form-check"><input class="form-check-input sales-col-toggle"
                                            type="checkbox" value="4" checked id="scol4"><label class="form-check-label"
                                            for="scol4">Balance</label></div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="table-responsive" style="max-height: 50vh; overflow-y: auto;">
                    <table class="table table-sm table-bordered align-middle" id="salesPreviewTable">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>OR #</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Balance</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="salesPreviewTableBody"></tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="3" class="text-end">TOTALS:</td>
                                <td class="text-end text-success" id="salesPreviewTotalAmount">₱0.00</td>
                                <td class="text-end text-danger" id="salesPreviewTotalBalance">₱0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class='modal-footer bg-light'>
                <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>
                <button type='button' class='btn btn-success' id="finalizeSalesExportBtn">
                    <i class="bi bi-file-earmark-excel me-2"></i>Download CSV
                </button>
                <button type='button' class='btn btn-primary' id="finalizeSalesPrintBtn">
                    <i class="bi bi-printer me-2"></i>Print PDF
                </button>
            </div>
        </div>
    </div>
</div>



<script src='https://code.jquery.com/jquery-3.7.1.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js'></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
    window.canDelete = <?php echo json_encode($canDelete); ?>;
    window.currentBranch = '<?php echo htmlspecialchars($currentBranch); ?>';
    window.isBranchPage = false;
</script>
<script src="../js/spareparts_stock_card.js?v=<?php echo time(); ?>"></script>
<script src='../js/spareparts_inventory.js?v=<?php echo time(); ?>'></script>


<!-- VIEW TRANSFER MODAL -->
<div class='modal fade' id='viewTransferDetailsModal' tabindex='-1' aria-hidden='true'>
    <div class='modal-dialog modal-lg'>
        <div class='modal-content border-0 shadow'>
            <div class='modal-header bg-dark -green text-white border-0'>
                <h5 class='modal-title text-white'><i class="bi bi-info-circle me-2"></i>Transfer Details</h5>
                <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
            </div>
            <div class='modal-body p-4' id="transferDetailsBody">
                <div class="text-center p-4">
                    <div class="spinner-border text-dark-green"></div>
                </div>
            </div>
            <div class='modal-footer border-0 bg-light'>
                <button type='button' class='btn btn-outline-dark-green fw-bold px-4'
                    data-bs-dismiss='modal'>Close</button>
                <button type='button' class='btn btn-danger d-none' id="rejectTransferBtn">Reject Transfer</button>
                <button type='button' class='btn btn-dark-green d-none text-white' id="confirmReceiveBtn">Receive
                    Items</button>
            </div>
        </div>
    </div>
</div>

<!-- REPORT MODALS -->
<div class='modal fade' id='inventoryReportsModal' tabindex='-1' aria-hidden='true'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header bg-dark text-white'>
                <h5 class='modal-title text-white'><i class="bi bi-file-earmark-bar-graph me-2 "></i>Generate
                    Inventory
                    Reports</h5>
                <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
            </div>
            <div class='modal-body p-4'>
                <form id="inv_generateInventoryReportForm">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted text-uppercase">Report
                            Type</label>
                        <select class="form-select border-0 bg-light fw-bold" id="inv_report_type" required>
                            <option value="inventory_balance">Inventory Balance Report</option>
                            <option value="inventory_summary">Inventory Summary (Tally Board)</option>
                            <option value="transferred_stocks">Summary of Transferred Stocks</option>
                            <option value="received_stocks">Summary of Received Stocks</option>
                            <option value="delivered_stocks">Summary of Delivered Stocks</option>
                            <option value="stock_in">Legacy: Stocks In</option>
                            <option value="stock_out">Legacy: Stocks Out</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted text-uppercase">Time
                            Period</label>
                        <select class="form-select border-0 bg-light fw-bold" id="inv_report_period" required>
                            <option value="monthly">Monthly</option>
                            <option value="daily">Daily / As of Date</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    <div class="mb-3" id="inv_report_date_container">
                        <label class="form-label fw-bold small text-muted text-uppercase">Select
                            Date</label>
                        <input type="date" class="form-control border-0 bg-light fw-bold" id="inv_report_date" required>
                    </div>
                    <div class="mb-3 d-none" id="inv_report_month_container">
                        <label class="form-label fw-bold small text-muted text-uppercase">Select
                            Month/Year</label>
                        <input type="month" class="form-control border-0 bg-light fw-bold" id="inv_report_month">
                    </div>
                    <div class="mb-3 d-none" id="inv_report_year_container">
                        <label class="form-label fw-bold small text-muted text-uppercase">Select
                            Year</label>
                        <input type="number" class="form-control border-0 bg-light fw-bold" id="inv_report_year"
                            min="2000" max="2099">
                    </div>
                    <div class="mb-3 d-none" id="inv_report_custom_container">
                        <label class="form-label fw-bold small text-muted text-uppercase">Custom Range</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="date" class="form-control border-0 bg-light fw-bold"
                                    id="inv_report_date_start">
                            </div>
                            <div class="col-6">
                                <input type="date" class="form-control border-0 bg-light fw-bold"
                                    id="inv_report_date_end">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3" id="inv_report_branch_container">
                        <label class="form-label fw-bold small text-muted text-uppercase">Branch</label>
                        <select class="form-select border-0 bg-light fw-bold" id="inv_report_branch">
                            <option value="all">All Branches</option>
                        </select>
                    </div>
                    <hr>
                    <h6 class="fw-bold text-muted mb-3"><i class="bi bi-funnel me-1"></i>Filters (Optional)
                    </h6>
                    <div class="row g-2">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small text-muted text-uppercase">Category</label>
                            <input type="text" class="form-control border-0 bg-light fw-bold"
                                id="inv_report_filter_category" placeholder="e.g., Engine, Body...">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small text-muted text-uppercase">Brand</label>
                            <input type="text" class="form-control border-0 bg-light fw-bold"
                                id="inv_report_filter_brand" placeholder="e.g., Honda, Yamaha...">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted text-uppercase">Specific Part No.</label>
                        <input type="text" class="form-control border-0 bg-light fw-bold" id="inv_report_filter_part_no"
                            placeholder="Search by Part Number...">
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="inv_report_filter_low_stock">
                        <label class="form-check-label fw-bold small text-muted text-uppercase"
                            for="inv_report_filter_low_stock">Show Low Stock Items Only</label>
                    </div>
                </form>
            </div>
            <div class='modal-footer border-0 p-4 pt-0'>
                <button type='button' class='btn btn-light fw-bold px-4' data-bs-dismiss='modal'>Close</button>
                <button type='button' class='btn btn-primary fw-bold px-4 text-white' id="btnGenerateReport">
                    <i class="bi bi-eye me-2 "></i>Preview Report
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Transfer  Modal -->
<div class="modal fade" id="transferReportsModal" tabindex="-1" aria-labelledby="transferReportsModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fw-bold text-white" id="transferReportsModalLabel">
                    <i class="bi bi-file-earmark-bar-graph me-2"></i>Generate Transfer Reports
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="transfer_generateReportForm">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase text-muted">Report Type</label>
                        <select class="form-select border-2" id="t_report_type" name="report_type">
                            <option value="all">All Transfers</option>
                            <option value="Completed">Completed Transfers</option>
                            <option value="In-Transit">In-Transit Transfers</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase text-muted">Time Period</label>
                        <select class="form-select border-2" id="t_report_period" name="period">
                            <option value="daily">Daily Report</option>
                            <option value="monthly">Monthly Report</option>
                            <option value="yearly">Yearly Report</option>
                        </select>
                    </div>

                    <div id="t_report_date_container">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase text-muted">Select Date</label>
                            <input type="date" class="form-control border-2" id="t_report_date" name="date_value">
                        </div>
                    </div>

                    <div id="t_report_month_container" class="d-none">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase text-muted">Select Month</label>
                            <input type="month" class="form-control border-2" id="t_report_month">
                        </div>
                    </div>

                    <div id="t_report_year_container" class="d-none">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase text-muted">Select Year</label>
                            <input type="number" class="form-control border-2" id="t_report_year" min="2000" max="2100">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase text-muted">Filter by Branch</label>
                        <select class="form-select border-2" id="t_report_branch" name="branch">
                            <option value="all">All Branches</option>
                            <!-- Dynamic Loading -->
                        </select>
                        <div class="form-text x-small mt-1"><i class="bi bi-info-circle me-1"></i>Filters transfers
                            where this branch is either the origin or destination.</div>
                    </div>

                    <div class="d-grid gap-2 mt-4">
                        <button type="button" class="btn btn-primary btn-lg fw-bold text-white"
                            id="btnGenerateTransferReport">
                            <i class="bi bi-eye me-2"></i>Generate Preview
                        </button>
                        <div class="row g-2 mt-1">
                            <div class="col-6">
                                <button type="button" class="btn btn-outline-danger w-100"
                                    id="btnGenerateTransferReportPdf">
                                    <i class="bi bi-file-pdf me-2"></i>PDF
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="button" class="btn btn-outline-success w-100"
                                    id="btnGenerateTransferReportExcel">
                                    <i class="bi bi-file-excel me-2"></i>Excel
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class='modal fade' id='inventoryPreviewModal' tabindex='-1' aria-hidden='true'>
    <div class='modal-dialog modal-xl'>
        <div class='modal-content'>
            <div class='modal-header bg-dark text-white'>
                <h5 class='modal-title text-white'><i class="bi bi-eye me-2"></i> Report Preview
                </h5>
                <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
            </div>
            <div class='modal-body p-4'>
                <!-- PRINT HEADER (Hidden on Screen) -->
                <div class="print-only d-none mb-4">
                    <div class="text-center">
                        <h4 class="fw-bold mb-0">ROXAS CITY SOLID MERCHANDISING</h4>
                        <p class="small mb-0">1031 Victoria Bldg.,Roxas Avenue, Roxas City, Capiz</p>
                        <div style="border-bottom: 2px solid #333; margin-bottom: 20px;"></div>
                        <h5 class="text-uppercase fw-bold mb-1" id="printReportTitleDisplay">OFFICIAL REPORT</h5>
                        <p class="small text-muted">Date Generated: <?php echo date('F d, Y h:i A'); ?></p>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                    <div>
                        <h6 class="fw-bold mb-1" id="inventoryPreviewSubtitle">Report Details</h6>
                        <p class="small text-muted mb-0">Review the records below before finalized printing or
                            exporting.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <input type="text" id="inventoryPreviewSearch" class="form-control form-control-sm"
                            placeholder="Filter records..." style="width: 200px;">
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-9 border-end" id="reportPreviewMainCol">
                        <div id="reportSummaryTabsArea" class="mb-3 no-print"></div>
                        <div class="table-responsive" style="max-height: 70vh; overflow-y: auto;">
                            <table class="table table-sm table-bordered align-middle" id="inventoryPreviewTable">
                                <thead class="table-light sticky-top" id="inventoryPreviewHead"></thead>
                                <tbody id="inventoryPreviewTableBody"></tbody>
                                <tfoot class="table-light fw-bold" id="inventoryPreviewFoot"></tfoot>
                            </table>
                        </div>
                        <div id="reportBrandSummaryArea" class="mt-4"></div>
                    </div>
                    <div class="col-lg-3" id="reportSummarySidebarCol">
                        <div id="reportSummarySidebar"></div>
                    </div>
                </div>

                <!-- PRINT FOOTER / SIGNATURES (Hidden on Screen) -->
                <div class="print-only d-none mt-5 pt-4">
                    <div class="row">
                        <div class="col-4 text-center">
                            <div class="border-top border-dark pt-2 mx-3">
                                <p class="small fw-bold mb-0">PREPARED BY:</p>
                            </div>
                        </div>
                        <div class="col-4"></div>
                        <div class="col-4 text-center">
                            <div class="border-top border-dark pt-2 mx-3">
                                <p class="small fw-bold mb-0">NOTED BY:</p>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-5">
                        <p class="small text-muted italic">*** This is a computer-generated report. ***</p>
                    </div>
                </div>
            </div>
            <div class='modal-footer bg-light border-top p-3'>
                <button type='button' class='btn btn-secondary fw-bold px-4' data-bs-dismiss='modal'>Close</button>
                <div class="dropdown">
                    <button class="btn btn-success fw-bold px-4 dropdown-toggle" type="button"
                        data-bs-toggle="dropdown">
                        <i class="bi bi-download me-2"></i>Export
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item fw-bold" href="#" id="finalizeInventoryExportBtn"><i
                                    class="bi bi-file-earmark-excel me-2 text-success"></i>Export to CSV</a>
                        </li>
                        <li><a class="dropdown-item fw-bold" href="#" id="finalizeInventoryExportExcelBtn"><i
                                    class="bi bi-file-earmark-spreadsheet me-2 text-success"></i>Export to
                                Excel (XLSX)</a></li>
                        <li><a class="dropdown-item fw-bold" href="#" id="finalizeInventoryExportPdfBtn"><i
                                    class="bi bi-file-earmark-pdf me-2 text-danger"></i>Export to PDF</a>
                        </li>
                    </ul>
                </div>
                <button type='button' class='btn btn-primary fw-bold px-4 text-white' id="finalizeInventoryPrintBtn">
                    <i class="bi bi-printer me-2"></i>Direct Print
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="generateSalesReportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white border-0">
                <h5 class="modal-title fw-bold text-white"><i class="bi bi-file-earmark-bar-graph me-2"></i>Generate
                    Sales Report</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form id="sales_generateSalesReportForm">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted text-uppercase">Time
                            Period</label>
                        <select class="form-select border-0 bg-light fw-bold" id="sales_report_period" required>
                            <option value="daily">Daily Report</option>
                            <option value="monthly">Monthly Report</option>
                            <option value="yearly">Yearly Report</option>
                        </select>
                    </div>
                    <div class="mb-3" id="sales_report_date_container">
                        <label class="form-label fw-bold small text-muted text-uppercase">Select
                            Date</label>
                        <input type="date" class="form-control border-0 bg-light fw-bold" id="sales_report_date"
                            required>
                    </div>
                    <div class="mb-3 d-none" id="sales_report_month_container">
                        <label class="form-label fw-bold small text-muted text-uppercase">Select
                            Month/Year</label>
                        <input type="month" class="form-control border-0 bg-light fw-bold" id="sales_report_month">
                    </div>
                    <div class="mb-3 d-none" id="sales_report_year_container">
                        <label class="form-label fw-bold small text-muted text-uppercase">Select
                            Year</label>
                        <input type="number" class="form-control border-0 bg-light fw-bold" id="sales_report_year"
                            min="2000" max="2099">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted text-uppercase">Branch</label>
                        <select class="form-select border-0 bg-light fw-bold" id="sales_report_branch">
                            <option value="all">All Branches</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted text-uppercase">Transaction
                            Type</label>
                        <select class="form-select border-0 bg-light fw-bold" id="sales_report_type">
                            <option value="all">All Transactions</option>
                            <option value="cash">Cash Only</option>
                            <option value="charge">Charge</option>
                        </select>
                    </div>
                    <hr>
                    <h6 class="fw-bold text-muted mb-3"><i class="bi bi-funnel me-1"></i>Filters (Optional)</h6>
                    <div class="mb-3">
                        <label class="form-label small text-muted text-uppercase">Filter by Brand</label>
                        <input type="text" class="form-control border-0 bg-light fw-bold" id="sales_report_filter_brand"
                            placeholder="e.g., Honda, Yamaha...">
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary fw-bold px-4 text-white" id="btnGenerateSalesReport">
                        <i class="bi bi-eye me-2"></i>Preview Report
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- NEW PAYMENT REPORT MODAL -->
<div class="modal fade" id="paymentReportsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white border-0">
                <h5 class="modal-title fw-bold text-white"><i class="bi bi-file-earmark-bar-graph me-2"></i>Generate
                    Payments
                    Report</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form id="payment_generateReportForm">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted text-uppercase">Time Period</label>
                        <select class="form-select border-0 bg-light fw-bold" id="payment_report_period" required>
                            <option value="daily">Daily Report</option>
                            <option value="monthly">Monthly Report</option>
                            <option value="yearly">Yearly Report</option>
                        </select>
                    </div>
                    <div class="mb-3" id="payment_report_date_container">
                        <label class="form-label fw-bold small text-muted text-uppercase">Select Date</label>
                        <input type="date" class="form-control border-0 bg-light fw-bold" id="payment_report_date"
                            required>
                    </div>
                    <div class="mb-3 d-none" id="payment_report_month_container">
                        <label class="form-label fw-bold small text-muted text-uppercase">Select Month/Year</label>
                        <input type="month" class="form-control border-0 bg-light fw-bold" id="payment_report_month">
                    </div>
                    <div class="mb-3 d-none" id="payment_report_year_container">
                        <label class="form-label fw-bold small text-muted text-uppercase">Select Year</label>
                        <input type="number" class="form-control border-0 bg-light fw-bold" id="payment_report_year"
                            min="2000" max="2099">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted text-uppercase">Branch</label>
                        <select class="form-select border-0 bg-light fw-bold" id="payment_report_branch">
                            <option value="all">All Branches</option>
                        </select>
                    </div>
                    <hr>
                    <h6 class="fw-bold text-muted mb-3"><i class="bi bi-funnel me-1"></i>Filters (Optional)</h6>
                    <div class="mb-3">
                        <label class="form-label small text-muted text-uppercase">Filter by Customer</label>
                        <input type="text" class="form-control border-0 bg-light fw-bold"
                            id="payment_report_filter_customer" placeholder="e.g., Juan Dela Cruz">