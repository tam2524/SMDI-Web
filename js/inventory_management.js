// =======================
// Global Variables
// =======================
let currentInventoryPage = 1;
let totalInventoryPages = 1;
let currentInventorySort = "";
let currentInventoryQuery = "";
let selectedRecordIds = [];
let selectedTransferIds = [];
let hasShownIncomingTransfers = false;
let shownTransferIds = [];
let lastCheckTime = new Date().toISOString();
let map;
let selectedMotorcycles = [];
let commonBranch = null;
let branchesMatch = true;
let currentReportData = null;
let currentReportMonth = null;
let currentReportBranch = null;
let currentReportType = null; 
let currentReportSummary = null;
let modelCount = 0;
let currentUserRole = "USER";

// =======================
// Document Ready & Event Listeners
// =======================
$(document).ready(function () {
  shownTransferIds = [];
  loadInventoryDashboard();
  loadInventoryTable();
  setupEventListeners();
  setInterval(checkIncomingTransfers, 1000);
  addModelForm();
  setTimeout(() => {
    if ($("#branchMap").length) {
      map = initMap(currentBranch);
    }
  }, 300);
});

// Modal management fixes
$(document).ready(function() {
    // Fix modal backdrop issues
    $(document).on('show.bs.modal', '.modal', function() {
        const zIndex = 1050 + (10 * $('.modal:visible').length);
        $(this).css('z-index', zIndex);
        setTimeout(() => {
            $('.modal-backdrop').not('.modal-stack').css('z-index', zIndex - 1).addClass('modal-stack');
        }, 0);
    });

    // Clean up modal backdrops
    $(document).on('hidden.bs.modal', '.modal', function() {
        $('.modal:visible').length && $(document.body).addClass('modal-open');
        
        // Remove extra backdrops
        if ($('.modal:visible').length === 0) {
            $('.modal-backdrop').remove();
            $(document.body).removeClass('modal-open');
        }
    });

    // Ensure body scroll is restored when all modals are closed
    $(document).on('hidden.bs.modal', function() {
        if ($('.modal.show').length === 0) {
            $('body').removeClass('modal-open').css('padding-right', '');
        }
    });
});

// Function to ensure modal is scrollable
function ensureModalScrollable(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.overflowY = 'auto';
        const modalBody = modal.querySelector('.modal-body');
        if (modalBody) {
            modalBody.style.overflowY = 'auto';
            modalBody.style.maxHeight = 'calc(100vh - 200px)';
        }
    }
}


function setupEventListeners() {
  // Input formatting
  $('body').on('input', '.engine-number, #editEngineNumber', function() {
    this.value = this.value.toUpperCase();
  });
  $('body').on('input', '.frame-number, #editFrameNumber', function() {
    this.value = this.value.toUpperCase();
  });

  // Search & filter
  $("#searchModelBtn").click(searchModels);
  $("#searchModel").keypress(function (e) {
    if (e.which === 13) searchModels();
  });
  $("#searchInventoryBtn").click(function () {
    currentInventoryQuery = $("#searchInventory").val();
    currentInventoryPage = 1;
    loadInventoryTable(currentInventoryPage, currentInventorySort, currentInventoryQuery);
  });
  $("#searchInventory").keypress(function (e) {
    if (e.which == 13) {
      currentInventoryQuery = $(this).val();
      currentInventoryPage = 1;
      loadInventoryTable(currentInventoryPage, currentInventorySort, currentInventoryQuery);
    }
  });
  $("#searchDashboardBtn").click(function () {
    loadInventoryDashboard($("#searchDashboard").val());
  });
  $("#searchDashboard").keypress(function (e) {
    if (e.which == 13) {
      loadInventoryDashboard($(this).val());
    }
  });

  $("#searchTransferReceiptBtn").click(function() {
    $("#searchTransferReceiptModal").modal("show");
});

$("#searchTransferBtn").click(searchTransferReceipt);
$("#transferInvoiceSearch").keypress(function(e) {
    if (e.which === 13) {
        searchTransferReceipt();
        e.preventDefault();
    }
});
   // Transfer selection event listeners
    $("#selectAllTransfers, #selectAllTransfersHeader").change(function() {
        const isChecked = $(this).prop('checked');
        $("#selectAllTransfers, #selectAllTransfersHeader").prop('checked', isChecked);
        
        $(".transfer-checkbox").prop('checked', isChecked);
        
        if (isChecked) {
            selectedTransferIds = [];
            $(".transfer-checkbox").each(function() {
                selectedTransferIds.push($(this).val());
            });
        } else {
            selectedTransferIds = [];
        }
        
        updateTransferSelection();
    });

    // Individual transfer selection
    $(document).on('change', '.transfer-checkbox', function() {
        const transferId = $(this).val();
        const isChecked = $(this).prop('checked');
        
        if (isChecked) {
            if (!selectedTransferIds.includes(transferId)) {
                selectedTransferIds.push(transferId);
            }
        } else {
            selectedTransferIds = selectedTransferIds.filter(id => id !== transferId);
        }
        
        // Update select all checkboxes
        const totalCheckboxes = $('.transfer-checkbox').length;
        const checkedCheckboxes = $('.transfer-checkbox:checked').length;
        
        $("#selectAllTransfers, #selectAllTransfersHeader").prop('checked', 
            totalCheckboxes > 0 && checkedCheckboxes === totalCheckboxes);
        
        updateTransferSelection();
    });

    $(document).on('click', '.transfer-row', function(e) {
        if (e.target.type !== 'checkbox') {
            const checkbox = $(this).find('.transfer-checkbox');
            checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
        }
    });
// Accept selected transfers - IMPROVED VERSION
$(document).off('click', '#acceptSelectedBtn').on('click', '#acceptSelectedBtn', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    if (selectedTransferIds.length === 0) {
        showErrorModal("Please select at least one transfer to accept");
        return;
    }
    
    const selectedCount = selectedTransferIds.length;
    
    // Create a unique confirmation modal for transfers
    const confirmHtml = `
        <div class="modal fade" id="transferAcceptConfirmModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Accept Selected Transfers</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to accept ${selectedCount} selected transfer(s)?</p>
                        <p class="text-info"><small>These motorcycles will be added to your branch inventory.</small></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-success" id="confirmAcceptTransfersBtn">Accept Transfers</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    $('#transferAcceptConfirmModal').remove();
    
    // Add new modal to body
    $('body').append(confirmHtml);
    
    // Show modal
    $('#transferAcceptConfirmModal').modal('show');
    
    // Handle confirm button
    $('#confirmAcceptTransfersBtn').off('click').on('click', function() {
        $('#transferAcceptConfirmModal').modal('hide');
        acceptSelectedTransfers();
    });
});

// Reject selected transfers - IMPROVED VERSION
$(document).off('click', '#rejectSelectedBtn').on('click', '#rejectSelectedBtn', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    if (selectedTransferIds.length === 0) {
        showErrorModal("Please select at least one transfer to reject");
        return;
    }
    
    const selectedCount = selectedTransferIds.length;
    
    // Create a unique confirmation modal for rejection
    const confirmHtml = `
        <div class="modal fade" id="transferRejectConfirmModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Reject Selected Transfers</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to reject ${selectedCount} selected transfer(s)?</p>
                        <p class="text-warning"><small><i class="bi bi-exclamation-triangle me-1"></i>This action cannot be undone. Motorcycles will be returned to their original branches.</small></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" id="confirmRejectTransfersBtn">Reject Transfers</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    $('#transferRejectConfirmModal').remove();
    
    // Add new modal to body
    $('body').append(confirmHtml);
    
    // Show modal
    $('#transferRejectConfirmModal').modal('show');
    
    // Handle confirm button
    $('#confirmRejectTransfersBtn').off('click').on('click', function() {
        $('#transferRejectConfirmModal').modal('hide');
        rejectSelectedTransfers();
    });
});

  // Inventory Table Pagination & Sorting
  $(document).on("click", ".page-link", function (e) {
    e.preventDefault();
    if ($(this).parent().hasClass("disabled")) return;
    const oldPage = currentInventoryPage;
    if ($(this).attr("id") === "prevPage") {
      currentInventoryPage = Math.max(1, currentInventoryPage - 1);
    } else if ($(this).attr("id") === "nextPage") {
      currentInventoryPage = Math.min(totalInventoryPages, currentInventoryPage + 1);
    } else {
      currentInventoryPage = parseInt($(this).data("page"));
    }
    if (currentInventoryPage !== oldPage) {
      loadInventoryTable(currentInventoryPage, currentInventorySort, currentInventoryQuery);
    }
  });
  $(document).on("click", ".sortable-header", function () {
    const sortField = $(this).data("sort");
    currentInventorySort = currentInventorySort === sortField + "_asc"
      ? sortField + "_desc"
      : sortField + "_asc";
    loadInventoryTable(currentInventoryPage, currentInventorySort, currentInventoryQuery);
  });

  // Add/Edit Motorcycle
  $("#addMotorcycleForm").submit(function (e) {
    e.preventDefault();
    addMotorcycle();
  });
  $("#editMotorcycleForm").submit(function (e) {
    e.preventDefault();
    updateMotorcycle();
  });

  // Model Management
  $("#addModelBtn").click(function () {
    addModelForm();
  });
  $("#addMotorcycleForm").submit(function (e) {
    e.preventDefault();
    addMotorcycle();
  });

  // Transfer
  $("#transferSelectedBtn").prop("disabled", false);
  $("#transferSelectedBtn").click(transferSelectedMotorcycles);
  $("#multipleTransferForm").submit(function (e) {
    e.preventDefault();
    performMultipleTransfers();
  });
  $("#multipleTransferModal").on("hidden.bs.modal", function () {
    selectedMotorcycles = [];
    updateSelectedMotorcyclesList();
    $("#engineSearch").val("");
    $("#searchResults").html('<div class="text-center text-muted py-3">Search for motorcycles using engine number</div>');
  });

  // Transfer selection/search
  $("#searchEngineBtn").click(searchMotorcyclesByEngine);
  $("#engineSearch").keypress(function (e) {
    if (e.which == 13) {
      searchMotorcyclesByEngine();
      e.preventDefault();
    }
  });
  $("#clearSearchBtn").click(function () {
    $("#engineSearch").val("");
    $("#searchResults").html(`
      <div class='text-center text-muted py-4'>
        <i class='bi bi-search display-6 text-muted mb-2'></i>
        <p>Search for motorcycles to display results</p>
      </div>
    `);
    $("#searchResultsCount").text("0");
  });
  $("#clearSelectionBtn").click(function () {
    selectedMotorcycles = [];
    updateSelectedMotorcyclesList();
    $("#searchResults .transfer-search-result").removeClass("selected");
    $("#searchResults .select-btn").removeClass("btn-danger").addClass("btn-success").text("Select");
  });


  $(document).on("hidden.bs.modal", "#incomingTransfersModal", function () {
    hasShownIncomingTransfers = false;
  });

  // Sale
  $("#paymentType").change(handlePaymentTypeChange);

  // Reports
  $('#generateReportsButton').click(showMonthlyReportOptions);
  $('#generateReportBtn').click(generateReport);
  $(document).on('click', '#exportMonthlyReportToPDF', function() {
    generateReportPDF();
  });

  // Monthly Inventory Modal
  $("#generateMonthlyInventory").click(showMonthlyInventoryOptions);
  $("#reportPeriod").change(toggleReportOptions);
}

function searchTransferReceipt() {
    const transferInvoiceNumber = $("#transferInvoiceSearch").val().trim();
    
    if (!transferInvoiceNumber) {
        showErrorModal("Please enter a transfer invoice number");
        return;
    }
    
    $.ajax({
        url: "../api/inventory_management.php",
        method: "GET",
        data: {
            action: "search_transfer_receipt",
            transfer_invoice_number: transferInvoiceNumber
        },
        dataType: "json",
        success: function(response) {
            if (response.success) {
                displayTransferSearchResults(response.data);
            } else {
                showErrorModal(response.message || "No transfer receipt found");
                $("#searchResultsContainer").hide();
            }
        },
        error: function(xhr, status, error) {
            showErrorModal("Error searching transfer receipt: " + error);
            $("#searchResultsContainer").hide();
        }
    });
}

// Add this function to display search results
function displayTransferSearchResults(data) {
    const $resultsContainer = $("#transferSearchResults");
    $resultsContainer.empty();
    
    if (data.length === 0) {
        $resultsContainer.html('<div class="text-center text-muted py-3">No transfer receipts found</div>');
    } else {
        data.forEach(transfer => {
            const transferDate = formatDate(transfer.transfer_date);
            $resultsContainer.append(`
                <div class="transfer-result-item p-3 mb-2 border rounded" data-transfer-id="${transfer.id}">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">${transfer.transfer_invoice_number}</h6>
                            <p class="mb-1 small">From: ${transfer.from_branch} → To: ${transfer.to_branch}</p>
                            <p class="mb-0 small text-muted">Date: ${transferDate}</p>
                        </div>
                        <button class="btn btn-sm btn-primary text-white view-receipt-btn" 
                                data-transfer-id="${transfer.id}"
                                data-invoice-number="${transfer.transfer_invoice_number}">
                            <i class="bi bi-eye"></i> View Receipt
                        </button>
                    </div>
                </div>
            `);
        });
    }
    
    $("#searchResultsContainer").show();
    
    // Add click handler for view receipt buttons
    $(".view-receipt-btn").off("click").on("click", function() {
        const transferId = $(this).data("transfer-id");
        const invoiceNumber = $(this).data("invoice-number");
        loadTransferReceipt(transferId, invoiceNumber);
    });
}

// =======================
// Search MT
// =======================

function loadTransferReceipt(transferId, invoiceNumber) {
    $.ajax({
        url: "../api/inventory_management.php",
        method: "GET",
        data: {
            action: "get_transfer_receipt",
            transfer_id: transferId
        },
        dataType: "json",
        success: function(response) {
            if (response.success) {
                $("#searchTransferReceiptModal").modal("hide");
                showTransferReceipt(response.data);
            } else {
                showErrorModal(response.message || "Error loading transfer receipt");
            }
        },
        error: function(xhr, status, error) {
            showErrorModal("Error loading transfer receipt: " + error);
        }
    });
}
// =======================
// Modal Functions
// =======================
function showSuccessModal(message) {
  $("#successMessage").text(message);
  $("#successModal").modal("show");
  setTimeout(() => {
    $("#successModal").modal("hide");
  }, 2000);
}

function showErrorModal(message) {
  $("#errorMessage").text(message);
  $("#errorModal").modal("show");
  setTimeout(() => {
    $("#errorModal").modal("hide");
  }, 3000);
}

function showConfirmationModal(message, title, callback) {
  $("#confirmationMessage").text(message);
  $("#confirmationModalLabel").text(title);
  const modal = $("#confirmationModal");
  
  // Remove all existing click handlers to prevent conflicts
  modal.off("click", "#confirmActionBtn");
  
  // Add new click handler
  modal.on("click", "#confirmActionBtn", function () {
    modal.modal("hide");
    if (typeof callback === "function") {
      callback();
    }
  });
  
  modal.modal("show");
}

// =======================
// Inventory Table & Pagination
// =======================
function loadInventoryDashboard(
  searchTerm = "",
  sortBy = "model",
  sortOrder = "asc"
) {
  $("#inventoryCards").html(
    '<div class="col-12 text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>'
  );

  $.ajax({
    url: "../api/inventory_management.php",
    method: "GET",
    data: {
      action: "get_inventory_dashboard",
      search: searchTerm,
      include_brand: true,
    },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        let sortedData = response.data;

        sortedData.sort((a, b) => {
          let valueA, valueB;

          if (sortBy === "model") {
            valueA = a.model.toLowerCase();
            valueB = b.model.toLowerCase();
          } else if (sortBy === "brand") {
            valueA = a.brand.toLowerCase();
            valueB = b.brand.toLowerCase();
          } else {
            valueA = a.model.toLowerCase();
            valueB = b.model.toLowerCase();
          }

          if (valueA < valueB) return sortOrder === "asc" ? -1 : 1;
          if (valueA > valueB) return sortOrder === "asc" ? 1 : -1;
          return 0;
        });

        renderInventoryCards(sortedData);
      } else {
        $("#inventoryCards").html(
          '<div class="col-12 text-center py-5 text-danger">Error loading inventory data</div>'
        );
        showErrorModal(response.message || "Error loading dashboard data");
      }
    },
    error: function (xhr, status, error) {
      $("#inventoryCards").html(
        '<div class="col-12 text-center py-5 text-danger">Error loading inventory data: ' +
          error +
          "</div>"
      );
      showErrorModal("Error loading dashboard: " + error);
    },
  });
}
function renderInventoryCards(data) {
  let html = "";

  if (data.length === 0) {
    html = '<div class="col-12 text-center py-5 text-muted">No inventory data found</div>';
  } else {
    // Group data by brand
    const brands = {};
    data.forEach((item) => {
      if (!brands[item.brand]) {
        brands[item.brand] = [];
      }
      brands[item.brand].push(item);
    });

    // Sort brands alphabetically for consistent display
    const sortedBrands = Object.keys(brands).sort();

    // Render each brand section
    sortedBrands.forEach(brand => {
      // Get brand color
      let brandColor = "";
      switch (brand.toLowerCase()) {
        case "suzuki":
          brandColor = "border-primary bg-primary-light";
          break;
        case "honda":
          brandColor = "border-danger bg-danger-light";
          break;
        case "yamaha":
          brandColor = "border-black bg-black-light";
          break;
        case "kawasaki":
          brandColor = "border-success bg-success-light";
          break;
        case "asiastar":
          brandColor = "border-warning bg-warning-light";
          break;
        default:
          brandColor = "border-secondary bg-secondary-light";
      }

      // Add brand header
      html += `
        <div class="col-12 mb-3">
          <div class="brand-header p-3 ${brandColor}" style="border-radius: 8px; margin-bottom: 15px;">
            <h5 class="mb-0 fw-bold text-uppercase" style="color: #333; letter-spacing: 1px;">
              ${brand} <span class="badge bg-dark ms-2">${brands[brand].length} models</span>
            </h5>
          </div>
        </div>
      `;

      // Add model cards for this brand
      brands[brand].forEach((item) => {
        html += `
          <div class="col-xl-1 col-lg-2 col-md-3 col-sm-4 col-6 model-card-container px-1 mb-2">
            <div class="model-card d-flex justify-content-between align-items-center ${brandColor}" 
                 data-brand="${item.brand}" data-model="${item.model}" onclick="filterByModel('${item.brand}', '${item.model}')">
              <div class="model-name" title="${item.model}">${item.model}</div>
              <div class="quantity-badge">${item.total_quantity}</div>
            </div>
          </div>
        `;
      });

      // Add a separator between brands (except for the last one)
      if (brand !== sortedBrands[sortedBrands.length - 1]) {
        html += '<div class="col-12"><hr class="my-4"></div>';
      }
    });
  }

  $("#inventoryCards").html(html);
}


function filterByModel(brand, model) {
  $("#management-tab").tab("show");

  $("#searchInventory").val(model);
  currentInventoryQuery = model;
  currentInventoryPage = 1;

  loadInventoryTable(
    currentInventoryPage,
    currentInventorySort,
    currentInventoryQuery
  );
}
function loadInventoryTable(page = 1, sort = "", query = "") {
  $("#inventoryTableBody").html(
    '<tr><td colspan="11" class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>'
  );

  $.ajax({
    url: "../api/inventory_management.php",
    method: "GET",
    data: {
      action: "get_inventory_table",
      page: page,
      sort: sort,
      query: query,
    },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        currentInventoryPage = page;
        totalInventoryPages = response.pagination.totalPages || 1; 
        renderInventoryTable(response.data);
        updateInventoryPaginationControls(totalInventoryPages);
      } else {
        $("#inventoryTableBody").html(
          '<tr><td colspan="11" class="text-center py-5 text-danger">Error loading inventory data</td></tr>'
        );
        showErrorModal(response.message || "Error loading table data");
      }
    },
    error: function (xhr, status, error) {
      $("#inventoryTableBody").html(
        '<tr><td colspan="11" class="text-center py-5 text-danger">Error loading inventory data: ' +
          error +
          "</td></tr>"
      );
      showErrorModal("Error loading table: " + error);
    },
  });
}

function updateInventoryPaginationControls(totalPages) {
  let paginationHtml = "";
  const maxVisiblePages = 5;
  let startPage, endPage;

  if (totalPages <= maxVisiblePages) {
    startPage = 1;
    endPage = totalPages;
  } else {
    const half = Math.floor(maxVisiblePages / 2);
    if (currentInventoryPage <= half + 1) {
      startPage = 1;
      endPage = maxVisiblePages;
    } else if (currentInventoryPage >= totalPages - half) {
      startPage = totalPages - maxVisiblePages + 1;
      endPage = totalPages;
    } else {
      startPage = currentInventoryPage - half;
      endPage = currentInventoryPage + half;
    }
  }

  paginationHtml += `
        <li class="page-item ${currentInventoryPage === 1 ? "disabled" : ""}">
            <a class="page-link" href="#" id="prevPage">
                <i class="fas fa-chevron-left me-1"></i> Previous
            </a>
        </li>`;

  if (startPage > 1) {
    paginationHtml += `
            <li class="page-item">
                <a class="page-link" href="#" data-page="1">1</a>
            </li>`;
    if (startPage > 2) {
      paginationHtml += `
                <li class="page-item disabled">
                    <span class="page-link">...</span>
                </li>`;
    }
  }

  for (let i = startPage; i <= endPage; i++) {
    paginationHtml += `
            <li class="page-item ${currentInventoryPage === i ? "active" : ""}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>`;
  }

  if (endPage < totalPages) {
    if (endPage < totalPages - 1) {
      paginationHtml += `
                <li class="page-item disabled">
                    <span class="page-link">...</span>
                </li>`;
    }
    paginationHtml += `
            <li class="page-item">
                <a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a>
            </li>`;
  }

  paginationHtml += `
        <li class="page-item ${
          currentInventoryPage === totalPages ? "disabled" : ""
        }">
            <a class="page-link" href="#" id="nextPage">
                Next <i class="fas fa-chevron-right ms-1"></i>
            </a>
        </li>`;

  $("#paginationControls").html(paginationHtml);
}

function renderInventoryTable(data) {
  let html = "";

  if (data.length === 0) {
    html =
      '<tr><td colspan="12" class="text-center py-5 text-muted">No inventory data found</td></tr>';
  } else {
    data.forEach((item) => {
      // Add category badge styling
      let categoryBadge = '';
      if (item.category === 'brandnew') {
        categoryBadge = '<span class="badge bg-success">Brand New</span>';
      } else if (item.category === 'repo') {
        categoryBadge = '<span class="badge bg-warning text-dark">Repo</span>';
      }

      html += `
                <tr data-id="${item.id}">
                <td>${item.invoice_number || "N/A"}</td>
                    <td>${formatDate(item.date_delivered)}</td>
                    <td>${item.brand}</td>
                    <td>${item.model}</td>
                    <td>${categoryBadge}</td>
                    <td>${item.engine_number}</td>
                    <td>${item.frame_number}</td>
                    <td>${item.color}</td>
                    <td>${formatCurrency(item.inventory_cost)}</td>
                    <td>${item.current_branch}</td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary edit-btn">
                                <i class="bi bi-pencil"></i>
                            </button>
                         <button class="btn btn-outline-danger sell-btn">
    <i class="bi"></i> ₱
</button>

                        </div>
                    </td>
                </tr>
            `;
    });
  }

  $("#inventoryTableBody").html(html);
  setupTableActionButtons();
}


function setupTableActionButtons() {
  $(".edit-btn").click(function () {
    const id = $(this).closest("tr").data("id");
    loadMotorcycleForEdit(id);
  });
  $(".return-btn").click(function () {
    const id = $(this).closest("tr").data("id");
    showConfirmationModal(
      "Are you sure you want to return this motorcycle to Head Office?",
      "Return Motorcycle",
      function () {
        returnToHeadOffice(id);
      }
    );
  });
  $(".sell-btn").click(function () {
    const id = $(this).closest("tr").data("id");
    sellMotorcycle(id);
  });

  $("#markAsSoldBtn").click(function () {
    const id = $("#editId").val();
    $("#editMotorcycleModal").modal("hide");
    sellMotorcycle(id);
  });
}

function getStatusBadgeClass(status) {
  switch (status) {
    case "available":
      return "bg-success";
    case "sold":
      return "bg-danger";
    case "transferred":
      return "bg-warning text-dark";
    default:
      return "bg-secondary";
  }
}
// =======================
// Model Management
// =======================
function addModelForm() {
  modelCount++;
  const template = document.getElementById("modelFormTemplate");
  const clone = template.content.cloneNode(true);

  clone.querySelector(".model-number").textContent = `Model #${modelCount}`;

  clone
    .querySelector(".remove-model-btn")
    .addEventListener("click", function () {
      if ($(".model-form").length > 1) {
        $(this).closest(".model-form").remove();
        updateModelNumbers();
      } else {
        showErrorModal("You must have at least one model");
      }
    });

  const quantityInput = clone.querySelector(".model-quantity");
  quantityInput.addEventListener("change", function () {
    updateSpecificDetailsFields(this);
  });

  const branchInput = document.createElement("input");
  branchInput.type = "hidden";
  branchInput.className = "model-branch";
  branchInput.value = currentBranch;
  clone.querySelector(".card-body").appendChild(branchInput);

  setTimeout(() => {
    updateSpecificDetailsFields(quantityInput);
  }, 100);

  $("#modelFormsContainer").append(clone);
}

function updateSpecificDetailsFields(quantityInput) {
  const quantity = parseInt(quantityInput.value) || 1;
  const container = $(quantityInput)
    .closest(".model-form")
    .find(".specific-details-container");
  const detailsRows = container.find(".specific-details-row");
  const existingRows = detailsRows.length;

  const color = $(quantityInput)
    .closest(".model-form")
    .find(".model-color")
    .val();

  if (quantity > 0) {
    container.show();
  } else {
    container.hide();
    return;
  }

  const rowsContainer = container.find(".specific-details-rows");

  if (quantity > existingRows) {
    for (let i = existingRows; i < quantity; i++) {
      const rowHtml = `
                <div class="specific-details-row row g-3 align-items-end mb-3 border-bottom pb-3">
                    <div class="col-md-6">
                        <label class="form-label mb-1">Engine Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control engine-number" placeholder="Engine Number" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label mb-1">Frame Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control frame-number" placeholder="Frame Number" required>
                    </div>
                </div>
            `;
      rowsContainer.append(rowHtml);
    }
  } else if (quantity < existingRows) {
    const rowsToRemove = existingRows - quantity;
    for (let i = 0; i < rowsToRemove; i++) {
      rowsContainer.find(".specific-details-row").last().remove();
    }
  }
}

function updateModelNumbers() {
  $(".model-form").each(function (index) {
    $(this)
      .find(".model-number")
      .text(`Model #${index + 1}`);
  });
}
// =======================
// Motorcycle CRUD
// =======================
function addMotorcycle() {
  const formData = {
    action: "add_motorcycle",
    invoice_number: $("#invoiceNumber").val(),
    date_delivered: $("#dateDelivered").val(),
    branch: $("#branch").val(),
    models: [],
  };

  if (
    !formData.invoice_number ||
    !formData.date_delivered ||
    !formData.branch
  ) {
    showErrorModal("Please fill in invoice number, date delivered, and branch");
    return;
  }

  let hasErrors = false;
  $(".model-form").each(function () {
    const modelData = {
      brand: $(this).find(".model-brand").val(),
      model: $(this).find(".model-name").val(),
      category: $(this).find(".model-category").val(),
      color: $(this).find(".model-color").val(),
      inventory_cost: $(this).find(".model-inventoryCost").val(),
      quantity: $(this).find(".model-quantity").val(),
      details: [],
    };

    if (
      !modelData.brand ||
      !modelData.model ||
      !modelData.category ||
      !modelData.quantity ||
      !modelData.color
    ) {
      showErrorModal("Please fill in all required fields for each model");
      hasErrors = true;
      return false;
    }
    
    $(this)
      .find(".specific-details-row")
      .each(function () {
        const detail = {
          engine_number: $(this).find(".engine-number").val(),
          frame_number: $(this).find(".frame-number").val(),
        };

        if (!detail.engine_number || !detail.frame_number) {
          showErrorModal(
            "Please fill in all engine number and frame number fields"
          );
          hasErrors = true;
          return false;
        }

        modelData.details.push(detail);
      });

    if (hasErrors) return false;

    formData.models.push(modelData);
  });

  if (hasErrors) return;

  $.ajax({
    url: "../api/inventory_management.php",
    method: "POST",
    data: formData,
    dataType: "json",
    success: function (response) {
      // Log response to console for debugging
      console.log("Add Motorcycle Response:", response);
      
      if (response.console_message) {
        console.log("Backend Info:", response.console_message);
      }

      if (response.success) {
        $("#addMotorcycleModal").modal("hide");
        $(".modal-backdrop").remove();
        $("body").removeClass("modal-open");

        $("#addMotorcycleForm")[0].reset();
        $("#modelFormsContainer").empty();
        modelCount = 0;

        // Show different modals based on response type
        if (response.type === 'existing_invoice') {
          console.log("Using existing invoice:", response.message);
          showSuccessModal(response.message); // Show as success, not info
        } else {
          console.log("Created new invoice:", response.message);
          showSuccessModal(response.message);
        }

        loadInventoryDashboard();
        loadInventoryTable(
          currentInventoryPage,
          currentInventorySort,
          currentInventoryQuery
        );
      } else {
        // Log error to console
        console.error("Add Motorcycle Error:", response.message);
        
        // Only show error modal for critical errors that user needs to fix
        if (response.message.includes("DUPLICATE_ENGINE_NUMBER") || 
            response.message.includes("DUPLICATE_FRAME_NUMBER") ||
            response.message.includes("Missing required field")) {
          // showErrorModal(response.message);
        } else {
          // For other errors, just log to console and show generic message
          console.error("Technical Error:", response.message);
          showSuccessModal("Operation completed. Check console for details.");
        }
      }
    },
    error: function (xhr, status, error) {
      // Log AJAX errors to console
      console.error("AJAX Error:", {
        status: status,
        error: error,
        response: xhr.responseText
      });
      
      // Show generic error message to user
      showErrorModal("Connection error. Please try again.");
    },
  });
}

function showInfoModal(message) {
  $("#infoMessage").text(message);
  $("#infoModal").modal("show");
  setTimeout(() => {
    $("#infoModal").modal("hide");
  }, 3000);
}

function updateMotorcycle() {
  const formData = {
    action: "update_motorcycle",
    id: $("#editId").val(),
    date_delivered: $("#editDateDelivered").val(),
    brand: $("#editBrand").val(),
    model: $("#editModel").val(),
    category: $("#editCategory").val(),
    engine_number: $("#editEngineNumber").val(),
    frame_number: $("#editFrameNumber").val(),
    invoice_number: $("#editInvoiceNumber").val(),
    color: $("#editColor").val(),
    inventory_cost: $("#editInventoryCost").val(),
    current_branch: $("#editCurrentBranch").val(),
    status: $("#editStatus").val(),
  };

  if (
    !formData.id ||
    !formData.date_delivered ||
    !formData.brand ||
    !formData.model ||
    !formData.category ||
    !formData.engine_number ||
    !formData.frame_number ||
    !formData.color
  ) {
    showErrorModal("Please fill in all required fields");
    return;
  }

  $.ajax({
    url: "../api/inventory_management.php",
    method: "POST",
    data: formData,
    dataType: "json",
    success: function (response) {
      // Log response to console for debugging
      console.log("Update Motorcycle Response:", response);
      
      if (response.console_message) {
        console.log("Backend Info:", response.console_message);
      }

      if (response.success) {
        $("#editMotorcycleModal").modal("hide");
        
        // Show different modals based on response type
        if (response.type === 'existing_invoice') {
          console.log("Using existing invoice:", response.message);
          showSuccessModal(response.message); // Show as success for existing invoice
        } else if (response.type === 'new_invoice') {
          console.log("Created new invoice:", response.message);
          showSuccessModal(response.message); // Show as success for new invoice
        } else {
          showSuccessModal(response.message || "Motorcycle updated successfully!");
        }
        
        loadInventoryTable(
          currentInventoryPage,
          currentInventorySort,
          currentInventoryQuery
        );
      } else {
        // Log error to console
        console.error("Update Motorcycle Error:", response.message);
        
        // Only show error modal for critical errors that user needs to fix
        if (response.message.includes("DUPLICATE_ENGINE_NUMBER") || 
            response.message.includes("DUPLICATE_FRAME_NUMBER") ||
            response.message.includes("Missing required field")) {
          showErrorModal(response.message);
        } else {
          // For other errors, just log to console and show generic message
          console.error("Technical Error:", response.message);
          showSuccessModal("Update completed. Check console for details.");
        }
      }
    },
    error: function (xhr, status, error) {
      // Log AJAX errors to console
      console.error("AJAX Error:", {
        status: status,
        error: error,
        response: xhr.responseText
      });
      
      // Show generic error message to user
      showErrorModal("Connection error. Please try again.");
    },
  });
}



function loadMotorcycleForEdit(id) {
  $.ajax({
    url: "../api/inventory_management.php",
    method: "GET",
    data: {
      action: "get_motorcycle",
      id: id,
    },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        const data = response.data;
        $("#editId").val(data.id);
        $("#editDateDelivered").val(data.date_delivered);
        $("#editBrand").val(data.brand);
        $("#editModel").val(data.model);
        $("#editCategory").val(data.category);
        $("#editEngineNumber").val(data.engine_number);
        $("#editFrameNumber").val(data.frame_number);
        $("#editInvoiceNumber").val(data.invoice_number || "");
        $("#editColor").val(data.color);
        $("#editInventoryCost").val(data.inventory_cost);
        $("#editCurrentBranch").val(data.current_branch);
        $("#editStatus").val(data.status);

        $("#editMotorcycleModal").modal("show");
      } else {
        showErrorModal(response.message || "Error loading motorcycle data");
      }
    },
    error: function (xhr, status, error) {
      showErrorModal("Error loading motorcycle: " + error);
    },
  });
}


// =======================
// Invoice Validation
// =======================
// $("#invoiceNumber").on("blur", function () {
//   checkInvoiceNumber($(this).val());
// });
// $("#addMotorcycleForm").on("submit", function (e) {
//   const invoiceNumber = $("#invoiceNumber").val();
//   if (invoiceNumber) {
//     e.preventDefault();
//     checkInvoiceNumber(invoiceNumber, true);
//   }
// });
// function checkInvoiceNumber(invoiceNumber, isSubmit = false) {
//   if (!invoiceNumber) return;

//   $.ajax({
//     url: "../api/inventory_management.php",
//     method: "POST",
//     data: {
//       action: "check_invoice_number",
//       invoice_number: invoiceNumber,
//     },
//     dataType: "json",
//     success: function (response) {
//       if (response.exists) {
//         showInvoiceError("An invoice with this number already exists");
//         if (isSubmit) {
//           $("#invoiceNumber").focus();
//         }
//       } else {
//         clearInvoiceError();
//         if (isSubmit) {
//           $("#addMotorcycleForm").off("submit").submit();
//         }
//       }
//     },
//     error: function () {
//       if (isSubmit) {
//         $("#addMotorcycleForm").off("submit").submit();
//       }
//     },
//   });
// }

function showInvoiceError(message) {
  $("#invoiceNumber").addClass("is-invalid");
  $("#invoiceNumber").removeClass("is-valid");

  $("#invoiceNumber").next(".invalid-feedback").remove();

  $("#invoiceNumber").after(`<div class="invalid-feedback">${message}</div>`);
}

function clearInvoiceError() {
  $("#invoiceNumber").removeClass("is-invalid");
  $("#invoiceNumber").addClass("is-valid");
  $("#invoiceNumber").next(".invalid-feedback").remove();
}

// =======================
// Engine Number Validation
// =======================
$(document).on("blur", ".engine-number", function () {
  checkEngineNumber($(this).val(), $(this));
});

$(document).on("blur", "#editEngineNumber", function () {
  const excludeId = $("#editId").val();
  checkEngineNumber($(this).val(), $(this), excludeId);
});

function checkEngineNumber(engineNumber, $element, excludeId = 0) {
  if (!engineNumber) return;

  const data = {
    action: "check_engine_number",
    engine_number: engineNumber,
  };

  if (excludeId > 0) {
    data.exclude_id = excludeId;
  }

  $.ajax({
    url: "../api/inventory_management.php",
    method: "POST",
    data: data,
    dataType: "json",
    success: function (response) {
      if (response.exists) {
        showFieldError($element, "This engine number already exists in the system");
      } else {
        clearFieldError($element);
      }
    },
    error: function () {
      // On error, clear any existing error to avoid blocking the user
      clearFieldError($element);
    },
  });
}

// =======================
// Frame Number Validation
// =======================
$(document).on("blur", ".frame-number", function () {
  checkFrameNumber($(this).val(), $(this));
});

$(document).on("blur", "#editFrameNumber", function () {
  const excludeId = $("#editId").val();
  checkFrameNumber($(this).val(), $(this), excludeId);
});

function checkFrameNumber(frameNumber, $element, excludeId = 0) {
  if (!frameNumber) return;

  const data = {
    action: "check_frame_number",
    frame_number: frameNumber,
  };

  if (excludeId > 0) {
    data.exclude_id = excludeId;
  }

  $.ajax({
    url: "../api/inventory_management.php",
    method: "POST",
    data: data,
    dataType: "json",
    success: function (response) {
      if (response.exists) {
        showFieldError($element, "This frame number already exists in the system");
      } else {
        clearFieldError($element);
      }
    },
    error: function () {
      // On error, clear any existing error to avoid blocking the user
      clearFieldError($element);
    },
  });
}

// =======================
// Generic Field Error Functions
// =======================
function showFieldError($element, message) {
  $element.addClass("is-invalid");
  $element.removeClass("is-valid");

  $element.next(".invalid-feedback").remove();

  $element.after(`<div class="invalid-feedback">${message}</div>`);
}

function clearFieldError($element) {
  $element.removeClass("is-invalid");
  $element.addClass("is-valid");
  $element.next(".invalid-feedback").remove();
}


// =======================
// Sale Functions
// =======================
function sellMotorcycle(id) {
  $("#sellMotorcycleId").val(id);

  $("#saleForm")[0].reset();
  $("#codFields").hide();
  $("#installmentFields").hide();

  $("#sellMotorcycleModal").modal("show");
}

function handlePaymentTypeChange() {
  const paymentType = $("#paymentType").val();

  $("#codFields").hide();
  $("#installmentFields").hide();

  if (paymentType === "COD") {
    $("#codFields").show();
  } else if (paymentType === "Installment") {
    $("#installmentFields").show();
  }
}

function submitSale() {
  const formData = {
    action: "sell_motorcycle",
    motorcycle_id: $("#sellMotorcycleId").val(),
    sale_date: $("#saleDate").val(),
    customer_name: $("#customerName").val(),
    payment_type: $("#paymentType").val(),
  };

  if (formData.payment_type === "COD") {
    formData.dr_number = $("#drNumber").val();
    formData.cod_amount = $("#codAmount").val();
  } else if (formData.payment_type === "Installment") {
    formData.terms = $("#terms").val();
    formData.monthly_amortization = $("#monthlyAmortization").val();
  }

  if (
    !formData.sale_date ||
    !formData.customer_name ||
    !formData.payment_type
  ) {
    showErrorModal("Please fill in all required fields");
    return;
  }

  if (
    formData.payment_type === "COD" &&
    (!formData.dr_number || !formData.cod_amount)
  ) {
    showErrorModal("Please fill in DR Number and COD Amount for COD payment");
    return;
  }

  if (
    formData.payment_type === "Installment" &&
    (!formData.terms || !formData.monthly_amortization)
  ) {
    showErrorModal(
      "Please fill in Terms and Monthly Amortization for Installment payment"
    );
    return;
  }

  $.ajax({
    url: "../api/inventory_management.php",
    method: "POST",
    data: formData,
    dataType: "json",
    success: function (response) {
      if (response.success) {
        $("#sellMotorcycleModal").modal("hide");
        showSuccessModal("Motorcycle marked as sold successfully!");

        loadInventoryTable(
          currentInventoryPage,
          currentInventorySort,
          currentInventoryQuery
        );
      } else {
        showErrorModal(response.message || "Error marking motorcycle as sold");
      }
    },
    error: function (xhr, status, error) {
      showErrorModal("Error marking motorcycle as sold: " + error);
    },
  });
}
// =======================
// Transfer Functions
// =======================
function transferSelectedMotorcycles() {
  $("#multipleFromBranch").val(currentBranch);
  $("#multipleTransferDate").val(new Date().toISOString().split("T")[0]);
  $("#selectedCount").text("0");

  selectedMotorcycles = [];
  updateSelectedMotorcyclesList();
  $("#engineSearch").val("");
  $("#searchResults").html(`
        <div class='text-center text-muted py-4'>
            <i class='bi bi-search display-6 text-muted mb-2'></i>
            <p>Search for motorcycles to display results</p>
        </div>
    `);
  $("#searchResultsCount").text("0");

  const $toBranch = $("#multipleToBranch");
  $toBranch
    .empty()
    .append('<option value="">Select Destination Branch</option>');

  const branches = [
    "HEADOFFICE",
    "KINGDOM",
    "TANQUE",
    "ROXAS SUZUKI",
    "MAMBUSAO",
    "SIGMA",
    "PRC",
    "BAILAN",
    "CUARTERO",
    "JAMINDAN",
    "ROXAS HONDA",
    "ANTIQUE-1",
    "ANTIQUE-2",
    "DELGADO HONDA",
    "DELGADO SUZUKI",
    "JARO-1",
    "JARO-2",
    "KALIBO MABINI",
    "KALIBO SUZUKI",
    "ALTAVAS",
    "EMAP",
    "CULASI",
    "BACOLOD",
    "PASSI-1",
    "PASSI-2",
    "BALASAN",
    "GUIMARAS",
    "PEMDI",
    "EEMSI",
    "AJUY",
    "MINDORO ROXAS",
    "3S MINDORO",
    "MINDORO MANSALAY",
    "K-RIDERS ROXAS",
    "IBAJAY",
    "NUMANCIA"
  ];

  branches.forEach((branch) => {
    if (branch !== currentBranch) {
      $toBranch.append(`<option value="${branch}">${branch}</option>`);
    }
  });

  $("#multipleTransferModal").modal("show");
}


function performMultipleTransfers() {
  const selectedIds = selectedMotorcycles.map((m) => m.id);
  
  // Collect inventory costs from input fields
  const inventoryCosts = selectedMotorcycles.map(motorcycle => {
    const costInput = document.getElementById(`inventory-cost-${motorcycle.id}`);
    return costInput ? parseFloat(costInput.value) || motorcycle.inventory_cost : motorcycle.inventory_cost;
  });

  // Get the transfer invoice number from user input
  const transferInvoiceNumber = $("#multipleTransferInvoiceNumber").val().trim();

  if (selectedIds.length === 0) {
    showErrorModal("Please select at least one motorcycle to transfer");
    return;
  }

  if (!transferInvoiceNumber) {
    showErrorModal("Please enter a transfer invoice number");
    return;
  }

  const formData = {
    action: "transfer_multiple_motorcycles",
    motorcycle_ids: selectedIds.join(","),
    inventory_costs: inventoryCosts.join(","),
    from_branch: $("#multipleFromBranch").val(),
    to_branch: $("#multipleToBranch").val(),
    transfer_date: $("#multipleTransferDate").val(),
    transfer_invoice_number: transferInvoiceNumber,
    notes: $("#multipleTransferNotes").val(),
  };

  if (
    !formData.motorcycle_ids ||
    !formData.from_branch ||
    !formData.to_branch ||
    !formData.transfer_date ||
    !formData.transfer_invoice_number
  ) {
    showErrorModal("Please fill in all required fields");
    return;
  }

  if (formData.from_branch === formData.to_branch) {
    showErrorModal("Cannot transfer to the same branch");
    return;
  }

  $.ajax({
    url: "../api/inventory_management.php",
    method: "POST",
    data: formData,
    dataType: "json",
    success: function (response) {
      if (response.success) {
        $("#multipleTransferModal").modal("hide");
        
        // Show receipt modal with transfer details
        showTransferReceipt(response.receipt_data);
        
        showSuccessModal(
            "Transfer initiated successfully! Motorcycles will remain at current branch until accepted by destination."
        );
        loadInventoryTable(
            currentInventoryPage,
            currentInventorySort,
            currentInventoryQuery
        );

        selectedMotorcycles = [];
        updateSelectedMotorcyclesList();
        $("#engineSearch").val("");
        $("#searchResults").html(
            '<div class="text-center text-muted py-3">Search for motorcycles using engine number</div>'
        );
      } else {
        showErrorModal(response.message || "Error initiating transfer");
      }
    },
    error: function (xhr, status, error) {
      showErrorModal("Error initiating transfer: " + error);
    },
  });
}

// Update your showTransferReceipt function to handle both data structures
function showTransferReceipt(receiptData) {
    if (!receiptData) return;
    
    // Handle different data structures
    let headerData, motorcycles, totalCount, totalCost, notes, transferInvoiceNumber;
    
    if (receiptData.header) {
        // This is from the search/get_transfer_receipt API
        headerData = receiptData.header;
        motorcycles = receiptData.motorcycles;
        totalCount = receiptData.total_count;
        totalCost = receiptData.total_cost;
        notes = headerData.notes;
        transferInvoiceNumber = headerData.transfer_invoice_number;
    } else {
        // This is from the transfer_multiple_motorcycles API (original format)
        headerData = {
            transfer_date: new Date().toISOString().split('T')[0],
            from_branch: receiptData.from_branch,
            to_branch: receiptData.to_branch,
            notes: receiptData.notes,
            transfer_invoice_number: receiptData.transfer_invoice_number
        };
        motorcycles = receiptData.motorcycles;
        totalCount = receiptData.total_count;
        totalCost = receiptData.total_cost;
        notes = receiptData.notes;
        transferInvoiceNumber = receiptData.transfer_invoice_number;
    }
    
    // Set header information
    $("#receiptDate").text(formatDate(headerData.transfer_date));
    $("#receiptTransferId").text(headerData.id || 'N/A');
    $("#receiptInvoiceNo").text(transferInvoiceNumber || 'N/A');
    $("#receiptFromBranch").text(headerData.from_branch);
    $("#receiptToBranch").text(headerData.to_branch);
    
    // Set notes or show default message
    if (notes && notes.trim() !== '') {
        $("#receiptNotes").text(notes);
    } else {
        $("#receiptNotes").text('No notes provided.');
    }
    
    // Populate motorcycles list
    const $receiptList = $("#receiptMotorcyclesList");
    $receiptList.empty();
    
    let calculatedTotalCost = 0;
    
    motorcycles.forEach((motorcycle, index) => {
        const cost = parseFloat(motorcycle.inventory_cost) || 0;
        calculatedTotalCost += cost;
        
        $receiptList.append(`
            <tr>
                <td>${index + 1}</td>
                <td>${escapeHtml(motorcycle.brand)}</td>
                <td>${escapeHtml(motorcycle.model)}</td>
                <td>${escapeHtml(motorcycle.color)}</td>
                <td>${escapeHtml(motorcycle.engine_number)}</td>
                <td>${escapeHtml(motorcycle.frame_number)}</td>
                <td class="text-end">${formatCurrency(cost)}</td>
            </tr>
        `);
    });
    
    // Use calculated total if provided total is not available
    const finalTotalCost = totalCost || calculatedTotalCost;
    const finalTotalCount = totalCount || motorcycles.length;
    
    // Set totals
    $("#receiptTotalCount").text(finalTotalCount);
    $("#receiptTotalCost").text(formatCurrency(finalTotalCost));
    
    // Show the modal
    $("#transferReceiptModal").modal("show");
    
    // Add print functionality
    $("#printReceiptBtn").off("click").on("click", function() {
        printReceipt();
    });
}

function printReceipt() {
    // Get current date for filename
    const currentDate = new Date().toISOString().slice(0, 10);
    const title = `Transfer_Receipt_${$("#receiptInvoiceNo").text()}_${currentDate}`;
    
    // Create a printable version of the receipt
    const printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>${title}</title>
            <style>
    body { 
        font-family: Arial, sans-serif; 
        margin: 5px; 
        color: #333;
        line-height: 1.2;
        font-size: 11px;
    }

    .report-header { 
        text-align: center; 
        margin-bottom: 10px; 
        border-bottom: 1px solid #000f71;
        padding-bottom: 5px;
    }
    
    .report-header h4 { 
        color: #000f71; 
        font-weight: 600; 
        margin: 0;
        font-size: 14px;
    }
    
    .report-header h5 { 
        color: #495057; 
        font-weight: 500; 
        margin: 0;
        font-size: 11px;
    }
    
    .company-address {
        text-align: center;
        color: #666;
        font-size: 10px;
        margin-bottom: 8px;
    }
    
    table { 
        width: 100%; 
        border-collapse: collapse; 
        margin-bottom: 8px; 
        font-size: 10px;
    }
    
    th, td { 
        border: 1px solid #ddd; 
        padding: 3px; 
        text-align: left; 
    }
    
    th { 
        background-color: #f1f1f1; 
        font-weight: 600; 
        color: #333;
    }
    
    .card { 
        margin-bottom: 8px; 
        border: 1px solid #e9ecef; 
        border-radius: 3px; 
    }
    
    .card-header { 
        background-color: #f8f9fa; 
        padding: 4px; 
        border-bottom: 1px solid #e9ecef; 
        font-weight: 600;
        font-size: 11px;
    }
    
    .card-body {
        padding: 5px;
        font-size: 10px;
    }
    
    .info-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 3px;
        font-size: 10px;
    }
    
    .total-row {
        background-color: #f8f9fa;
        font-weight: 600;
    }
    
    .footer-info {
        margin-top: 10px;
        padding-top: 5px;
        border-top: 1px solid #ddd;
        text-align: center;
        font-size: 9px;
        color: #666;
    }

    @page {
        size: 80mm auto; /* Or 8.5in 5.5in for short coupon */
        margin: 5mm;
    }

    @media print {
        body { margin: 0; }
        .no-print { display: none !important; }
    }
</style>

        </head>
        <body>
            <div class="report-header">
                <h4>SOLID MOTORCYCLE DISTRIBUTORS, INC.</h4>
                <h5>Merchandise Transfer Receipt</h5>
            </div>
            <div class="info-grid">
                <div class="card">
                    <div class="card-header">Transfer Information</div>
                    <div class="card-body">
                        <div class="info-row">
                            <span class="info-label">Date:</span>
                            <span class="info-value">${$("#receiptDate").text()}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Transfer Invoice No:</span>
                            <span class="info-value">${$("#receiptInvoiceNo").text()}</span>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">Branch Information</div>
                    <div class="card-body">
                        <div class="info-row">
                            <span class="info-label">From Branch:</span>
                            <span class="info-value">${$("#receiptFromBranch").text()}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">To Branch:</span>
                            <span class="info-value">${$("#receiptToBranch").text()}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">Transferred Motorcycles</div>
                <div class="card-body">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Brand</th>
                                <th>Model</th>
                                <th>Color</th>
                                <th>Engine Number</th>
                                <th>Frame Number</th>
                                <th>Inventory Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${$("#receiptMotorcyclesList").html()}
                        </tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td colspan="6" style="text-align: right; font-weight: 600;">Total Motorcycles:</td>
                                <td style="font-weight: 600;">${$("#receiptTotalCount").text()}</td>
                            </tr>
                            <tr class="total-row">
                                <td colspan="6" style="text-align: right; font-weight: 600;">Total Inventory Cost:</td>
                                <td style="font-weight: 600;">${$("#receiptTotalCost").text()}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            
            <div class="card notes-section">
                <div class="card-header">Transfer Notes</div>
                <div class="card-body">
                    <div>${$("#receiptNotes").text() || 'No additional notes provided.'}</div>
                </div>
            </div>
            
           
            
            <div class="footer-info">
                <div>Generated on: ${new Date().toLocaleString()}</div>
                <div>Document Reference: ${$("#receiptInvoiceNo").text()}</div>
                
            </div>
        </body>
        </html>
    `;
    
    // Create a new window for printing
    const printWindow = window.open('', '_blank');
    printWindow.document.write(printContent);
    printWindow.document.close();
    printWindow.focus();
    
    // Wait for content to load before printing
    setTimeout(function() {
        printWindow.print();
        // printWindow.close(); // Uncomment if you want to automatically close after printing
    }, 250);
}


function searchMotorcyclesByEngine() {
  const searchTerm = $("#engineSearch").val().trim();

  if (!searchTerm) {
    showErrorModal("Please enter an engine number to search");
    return;
  }

  $.ajax({
    url: "../api/inventory_management.php",
    method: "GET",
    data: {
      action: "search_inventory_by_engine",
      query: searchTerm,
      field: "engine_number",
      include_inventory_cost: true,
      fuzzy_search: true,
    },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        if (response.data.length === 0) {
          $("#searchResults").html(`
                        <div class='text-center text-muted py-4'>
                            <i class='bi bi-search display-6 text-muted mb-2'></i>
                            <p>No matching motorcycles found in ${currentBranch} branch</p>
                        </div>
                    `);
        } else {
          displaySearchResults(response.data);
        }
      } else {
        showErrorModal(response.message || "Error searching motorcycles");
      }
    },
    error: function (xhr, status, error) {
      showErrorModal("Error searching motorcycles: " + error);
    },
  });
}
function displaySearchResults(data) {
  const $resultsContainer = $("#searchResults");
  $("#searchResultsCount").text(data.length);

  if (data.length === 0) {
    $resultsContainer.html(`
            <div class='text-center text-muted py-4'>
                <i class='bi bi-search display-6 text-muted mb-2'></i>
                <p>No motorcycles found</p>
            </div>
        `);
    return;
  }

  let html = "";
  data.forEach((motorcycle) => {
    const isSelected = selectedMotorcycles.some((m) => m.id === motorcycle.id);
    const inventoryCostValue = motorcycle.inventory_cost ? formatCurrency(motorcycle.inventory_cost) : "N/A";

    html += `
            <div class="transfer-search-result ${isSelected ? "selected" : ""}" 
                 onclick="toggleMotorcycleSelection(${motorcycle.id}, '${
      motorcycle.engine_number
    }', '${motorcycle.brand}', '${motorcycle.model}', '${motorcycle.color}', '${
      motorcycle.current_branch
    }', ${motorcycle.inventory_cost || 0})">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="engine-number">${
                          motorcycle.engine_number
                        }</div>
                        <div class="model-info">${motorcycle.brand} ${
      motorcycle.model
    } - ${motorcycle.color}</div>
                       <div class="inventoryCost-info small text-success">
   Inventory Cost: ${inventoryCostValue}
</div>

                        <div class="branch-info">
                            <i class="bi bi-geo-alt me-1"></i>${
                              motorcycle.current_branch
                            }
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm ${
                      isSelected ? "btn-danger" : "btn-success"
                    } select-btn ms-2"
                            onclick="event.stopPropagation(); toggleMotorcycleSelection(${
                              motorcycle.id
                            }, '${motorcycle.engine_number}', '${
      motorcycle.brand
    }', '${motorcycle.model}', '${motorcycle.color}', '${
      motorcycle.current_branch
    }', ${motorcycle.inventory_cost || 0})">
                        ${isSelected ? "Remove" : "Select"}
                    </button>
                </div>
            </div>
        `;
  });

  $resultsContainer.html(html);
}

function toggleMotorcycleSelection(
  id,
  engineNumber,
  brand,
  model,
  color,
  currentBranch,
  inventory_cost = 0
) {
  const index = selectedMotorcycles.findIndex((m) => m.id === id);

  if (index === -1) {
    selectedMotorcycles.push({
      id: id,
      engine_number: engineNumber,
      brand: brand,
      model: model,
      color: color,
      current_branch: currentBranch,
      inventory_cost: inventory_cost || 0,
    });
  } else {
    selectedMotorcycles.splice(index, 1);
  }

  updateSelectedMotorcyclesList();
  updateTransferSummary(); // This will now handle motorcycle selection context
  searchMotorcyclesByEngine();
}

// Function to update transfer summary
function updateTransferSummary(transfers) {
  // Add null/undefined check
  if (!transfers || !Array.isArray(transfers)) {
    transfers = [];
  }
  
  const totalUnits = transfers.length;
  const fromBranches = [...new Set(transfers.map(t => t.from_branch))].join(', ');
  
  $("#summaryTotalUnits").text(totalUnits);
  $("#summaryFromBranches").text(fromBranches);
}

function updateSelectedMotorcyclesList() {
  const $selectedList = $("#selectedMotorcyclesList");

  if (selectedMotorcycles.length === 0) {
    $selectedList.html(`
      <div class='text-center text-muted py-4'>
        <i class='bi bi-inbox display-6 text-muted mb-2'></i>
        <p>No motorcycles selected</p>
      </div>
    `);
    
    // Reset summary values
    $("#selectedCount").text("0");
    $("#totalInventoryCostValue").text(formatCurrency(0));
    $("#selectionProgress").css("width", "0%");
    return;
  }

  let html = "";
  selectedMotorcycles.forEach((motorcycle, index) => {
    const inventoryCostValue = motorcycle.inventory_cost ? formatCurrency(motorcycle.inventory_cost) : "N/A";

    html += `
      <div class="selected-motorcycle-item">
        <div class="d-flex justify-content-between align-items-start">
          <div class="flex-grow-1">
            <div class="d-flex align-items-center mb-1">
              <span class="badge bg-primary me-2">${index + 1}</span>
              <span class="fw-semibold text-primary">${motorcycle.engine_number}</span>
            </div>
            <div class="small text-muted mb-1">${motorcycle.brand} ${motorcycle.model} - ${motorcycle.color}</div>
            <div class="small">
              <i class="bi bi-geo-alt me-1"></i>${motorcycle.current_branch}
            </div>
          </div>
          <button type="button" class="btn btn-sm btn-outline-danger" 
                  onclick="removeMotorcycleFromSelection(${motorcycle.id})">
            <i class="bi bi-x"></i>
          </button>
        </div>
        <!-- Add inventory cost input field -->
        <div class="mt-2">
          <label class="form-label small">Inventory Cost</label>
          <div class="input-group input-group-sm">
            <span class="input-group-text">₱</span>
            <input type="number" step="0.01" class="form-control" 
                   id="inventory-cost-${motorcycle.id}" 
                   value="${motorcycle.inventory_cost || ''}" 
                   placeholder="Enter inventory cost"
                   onchange="updateMotorcycleCost(${motorcycle.id}, this.value)">
            <button class="btn btn-outline-success" type="button" onclick="saveCost(${motorcycle.id})">
              <i class="bi bi-check-lg"></i> Save
            </button>
          </div>
        </div>
      </div>
    `;
  });

  $selectedList.html(html);
  
  // Update the transfer summary after updating the list
  updateTransferSummary();
}

// Function to update motorcycle cost in real-time
function updateMotorcycleCost(motorcycleId, newCost) {
  const motorcycle = selectedMotorcycles.find(m => m.id === motorcycleId);
  if (motorcycle) {
    motorcycle.inventory_cost = parseFloat(newCost) || 0;
    updateTransferSummary(); // Update summary in real-time
  }
}


// Function to save cost for a specific motorcycle
// Function to save cost for a specific motorcycle
function saveCost(motorcycleId) {
  const costInput = document.getElementById(`inventory-cost-${motorcycleId}`);
  const newCost = parseFloat(costInput.value);
  
  if (!isNaN(newCost) && newCost >= 0) {
    // Update the motorcycle object
    const motorcycle = selectedMotorcycles.find(m => m.id === motorcycleId);
    if (motorcycle) {
      motorcycle.inventory_cost = newCost;
      showSuccessModal("Cost updated successfully!");
      updateTransferSummary(); // Update summary after saving
    }
  } else {
    showErrorModal("Please enter a valid cost value.");
  }
}

function removeMotorcycleFromSelection(id) {
  const index = selectedMotorcycles.findIndex((m) => m.id === id);
  if (index !== -1) {
    selectedMotorcycles.splice(index, 1);
    updateSelectedMotorcyclesList();
    updateTransferSummary();
    searchMotorcyclesByEngine();
  }
}
// =======================
// Incoming Transfers
// =======================
function checkIncomingTransfers() {
  if (!currentBranch) {
    console.error("Current branch not set");
    return;
  }

  $.ajax({
    url: "../api/inventory_management.php",
    method: "GET",
    data: {
      action: "get_incoming_transfers",
      last_check_time: lastCheckTime,
      current_branch: currentBranch,
    },
    dataType: "json",
    success: function (response) {
      if (response.success && response.data.length > 0) {
        const newTransfers = response.data.filter(
          (transfer) => !shownTransferIds.includes(transfer.transfer_id)
        );

        if (newTransfers.length > 0) {
          showIncomingTransfersModal(newTransfers);

          newTransfers.forEach((transfer) => {
            shownTransferIds.push(transfer.transfer_id);
          });

          lastCheckTime = new Date().toISOString();
        }
      }
    },
    error: function (xhr, status, error) {
      console.error("Error fetching incoming transfers:", error);
    },
  });
}
function showIncomingTransfersModal(transfers) {
  const tbody = $("#incomingTransfersBody");
  tbody.empty();
  selectedTransferIds = [];

  if (transfers.length === 0) {
    tbody.append(`
      <tr>
        <td colspan="9" class="text-center py-4 text-muted">No incoming transfers found</td>
      </tr>
    `);
    $("#transferSummary").hide();
  } else {
    transfers.forEach((transfer) => {
      const statusBadge = getTransferStatusBadge(transfer.transfer_status);
      tbody.append(`
        <tr class="transfer-row" data-transfer-id="${transfer.transfer_id}">
          <td>
            <input type="checkbox" class="form-check-input transfer-checkbox" 
                   value="${transfer.transfer_id}">
          </td>
          <td>${transfer.brand} ${transfer.model}</td>
          <td><code>${transfer.engine_number}</code></td>
          <td><code>${transfer.frame_number}</code></td>
          <td>${transfer.color}</td>
          <td>${formatDate(transfer.transfer_date)}</td>
          <td>
            <span class="badge bg-info">${transfer.from_branch}</span>
          </td>
          <td>${transfer.transfer_invoice_number || 'N/A'}</td>
          <td>${statusBadge}</td>
        </tr>
      `);
    });
    
    // Pass the transfers parameter correctly
    updateTransferSummary(transfers);
  }

  updateTransferSelection();

  if (!hasShownIncomingTransfers) {
    $("#incomingTransfersModal").modal("show");
    hasShownIncomingTransfers = true;
  }
}

function getTransferStatusBadge(status) {
    switch(status) {
        case 'pending':
            return '<span class="badge bg-warning text-dark status-badge">Pending</span>';
        case 'completed':
            return '<span class="badge bg-success status-badge">Completed</span>';
        case 'rejected':
            return '<span class="badge bg-danger status-badge">Rejected</span>';
        default:
            return '<span class="badge bg-secondary status-badge">Unknown</span>';
    }
}

// Function to update transfer selection UI
function updateTransferSelection() {
    const selectedCount = selectedTransferIds.length;
    
    // Update counter
    $("#selectedTransfersCount").text(`${selectedCount} selected`);
    
    // Enable/disable buttons
    $("#acceptSelectedBtn, #rejectSelectedBtn").prop('disabled', selectedCount === 0);
    
    // Update row styling
    $(".transfer-row").removeClass('selected');
    selectedTransferIds.forEach(id => {
        $(`.transfer-row[data-transfer-id='${id}']`).addClass('selected');
    });
    
    // Show/hide summary
    if (selectedCount > 0) {
        updateSelectedTransfersSummary();
        $("#transferSummary").show();
    } else {
        $("#transferSummary").hide();
    }
}

// Function to update transfer summary
// Function to update transfer summary
function updateTransferSummary(transfers) {
  // Handle different contexts - incoming transfers vs motorcycle selection
  if (transfers && Array.isArray(transfers)) {
    // This is for incoming transfers context
    const totalUnits = transfers.length;
    const fromBranches = transfers.length > 0 ? 
      [...new Set(transfers.map(t => t.from_branch))].join(', ') : 
      '-';
    
    $("#summaryTotalUnits").text(totalUnits);
    $("#summaryFromBranches").text(fromBranches);
  } else {
    // This is for motorcycle selection context - calculate from selectedMotorcycles
    const selectedCount = selectedMotorcycles.length;
    const totalInventoryCost = selectedMotorcycles.reduce(
      (sum, motorcycle) => sum + (parseFloat(motorcycle.inventory_cost) || 0),
      0
    );
    
    // Update the selection progress and cost
    $("#selectedCount").text(selectedCount);
    $("#totalInventoryCostValue").text(formatCurrency(totalInventoryCost));
    
    // Update progress bar
    const progressPercentage = Math.min((selectedCount / 10) * 100, 100);
    $("#selectionProgress").css("width", progressPercentage + "%");
    
    // Update branch summary if elements exist
    if ($("#summaryTotalUnits").length) {
      $("#summaryTotalUnits").text(selectedCount);
    }
    
    // Get unique branches from selected motorcycles
    const uniqueBranches = [...new Set(selectedMotorcycles.map(m => m.current_branch))];
    if ($("#summaryFromBranches").length) {
      $("#summaryFromBranches").text(uniqueBranches.join(', ') || '-');
    }
  }
}

// Function to update selected transfers summary
function updateSelectedTransfersSummary() {
    const selectedCount = selectedTransferIds.length;
    const selectedBranches = [];
    
    selectedTransferIds.forEach(id => {
        const row = $(`.transfer-row[data-transfer-id='${id}']`);
        const branch = row.find('td:nth-child(7) .badge').text();
        if (branch && !selectedBranches.includes(branch)) {
            selectedBranches.push(branch);
        }
    });
    
    $("#summarySelectedCount").text(selectedCount);
    $("#summaryFromBranches").text(selectedBranches.join(', ') || '-');
}

function acceptSelectedTransfers() {
  if (selectedTransferIds.length === 0) {
    showErrorModal("No transfers selected");
    return;
  }

  // Show loading state
  $("#acceptSelectedBtn").prop('disabled', true).html('<i class="spinner-border spinner-border-sm me-2"></i>Processing...');

  $.ajax({
    url: "../api/inventory_management.php",
    method: "POST",
    data: {
      action: "accept_transfers",
      transfer_ids: selectedTransferIds.join(","),
      current_branch: currentBranch,
    },
    dataType: "json",
    success: function(response) {
      if (response.success) {
        showSuccessModal(response.message || "Selected transfers accepted successfully!");
        $("#incomingTransfersModal").modal("hide");
        
        // Reset state
        selectedTransferIds = [];
        hasShownIncomingTransfers = false;
        
        // Reload page after delay
        setTimeout(function() {
          window.location.reload();
        }, 2000);
      } else {
        showErrorModal(response.message || "Error accepting transfers");
        // Re-enable button
        $("#acceptSelectedBtn").prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i>Accept Selected');
      }
    },
    error: function(xhr, status, error) {
      console.error("AJAX Error:", xhr.responseText);
      showErrorModal("Error accepting transfers: " + error);
      // Re-enable button
      $("#acceptSelectedBtn").prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i>Accept Selected');
    },
  });
}


// Function to reject selected transfers
function rejectSelectedTransfers() {
  if (selectedTransferIds.length === 0) {
    showErrorModal("No transfers selected");
    return;
  }

  // Show loading state
  $("#rejectSelectedBtn").prop('disabled', true).html('<i class="spinner-border spinner-border-sm me-2"></i>Processing...');

  $.ajax({
    url: "../api/inventory_management.php",
    method: "POST",
    data: {
      action: "reject_transfers",
      transfer_ids: selectedTransferIds.join(","),
      current_branch: currentBranch,
    },
    dataType: "json",
    success: function(response) {
      if (response.success) {
        showSuccessModal(response.message || "Selected transfers rejected successfully!");
        
        // Remove rejected transfers from the modal
        selectedTransferIds.forEach(id => {
          $(`.transfer-row[data-transfer-id='${id}']`).fadeOut(300, function() {
            $(this).remove();
            
            // Check if any transfers remain
            if ($("#incomingTransfersBody tr:visible").length === 0) {
              $("#incomingTransfersBody").html(`
                <tr>
                  <td colspan="9" class="text-center py-4 text-muted">No pending transfers remaining</td>
                </tr>
              `);
              $("#transferSummary").hide();
            }
          });
        });
        
        selectedTransferIds = [];
        updateTransferSelection();
        
        // Re-enable button
        $("#rejectSelectedBtn").prop('disabled', false).html('<i class="bi bi-x-circle me-1"></i>Reject Selected');
      } else {
        showErrorModal(response.message || "Error rejecting transfers");
        // Re-enable button
        $("#rejectSelectedBtn").prop('disabled', false).html('<i class="bi bi-x-circle me-1"></i>Reject Selected');
      }
    },
    error: function(xhr, status, error) {
      console.error("AJAX Error:", xhr.responseText);
      showErrorModal("Error rejecting transfers: " + error);
      // Re-enable button
      $("#rejectSelectedBtn").prop('disabled', false).html('<i class="bi bi-x-circle me-1"></i>Reject Selected');
    },
  });
}


// =======================
// Branch Inventory & Map
// =======================

function viewModelDetails(id) {
  $("#motorcycleDetails").html(
    '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>'
  );

  $.get(
    "../api/inventory_management.php",
    {
      action: "get_motorcycle",
      id: id,
    },
    function (response) {
      if (response.success) {
        const item = response.data;
        let detailsHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-black">Basic Information</h6>
                        <hr>
                        <p><strong>Invoice Number/MT:</strong> ${
                          item.invoice_number || "N/A"
                        }</p>
                        <p><strong>Brand:</strong> ${item.brand}</p>
                        <p><strong>Model:</strong> ${item.model}</p>
                        <p><strong>Color:</strong> ${item.color}</p>
                        <p><strong>Current Branch:</strong> ${
                          item.current_branch
                        }</p>
                        <p><strong>Status:</strong> <span class="badge ${getStatusClass(
                          item.status
                        )}">
                            ${
                              item.status.charAt(0).toUpperCase() +
                              item.status.slice(1)
                            }
                        </span></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-black">Identification & Pricing</h6>
                        <hr>
                        <p><strong>Engine #:</strong> ${item.engine_number}</p>
                        <p><strong>Frame #:</strong> ${item.frame_number}</p>
                        <p><strong>Date Delivered:</strong> ${formatDate(
                          item.date_delivered
                        )}</p>
                        <p><strong>Inventory Cost:</strong> ${
                          item.inventory_cost ? formatCurrency(item.inventory_cost) : "N/A"
                        }</p>
                    </div>
                </div>
            `;

        if (item.transfer_history && item.transfer_history.length > 0) {
          detailsHTML += `
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <h6 class="text-primary">Transfer History</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>From Branch</th>
                                            <th>To Branch</th>
                                            <th>Notes</th>
                                            <th>Transferred By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                `;

          item.transfer_history.forEach((transfer) => {
            detailsHTML += `
                        <tr>
                            <td>${formatDate(transfer.transfer_date)}</td>
                            <td>${transfer.from_branch}</td>
                            <td>${transfer.to_branch}</td>
                            <td>${transfer.notes || "N/A"}</td>
                            <td>${transfer.transferred_by_name || "N/A"}</td>
                        </tr>
                    `;
          });

          detailsHTML += `
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                `;
        }

        if (item.latitude && item.longitude) {
          detailsHTML += `
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <h6 class="text-primary">Location</h6>
                            <div id="mapid" style="height: 300px;"></div>
                        </div>
                    </div>
                `;
        }

        $("#motorcycleDetails").html(detailsHTML);

        if (item.latitude && item.longitude) {
          setTimeout(() => {
            const container = document.getElementById("mapid");
            if (container) {
              if (container._leaflet_id) {
                container._leaflet_id = null;
              }

              const map = L.map("mapid").setView(
                [item.latitude, item.longitude],
                14
              );
              L.tileLayer(
                "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
                {
                  maxZoom: 19,
                }
              ).addTo(map);

              L.marker([item.latitude, item.longitude])
                .addTo(map)
                .bindPopup(
                  `${item.brand} ${item.model}<br>${item.engine_number}`
                )
                .openPopup();
            }
          }, 100);
        }
        $("#detailsModal").modal("show");
      } else {
        $("#motorcycleDetails").html(
          '<div class="alert alert-danger">Error loading motorcycle details</div>'
        );
        $("#detailsModal").modal("show");
      }
    },
    "json"
  ).fail(function () {
    $("#motorcycleDetails").html(
      '<div class="alert alert-danger">Error loading motorcycle details</div>'
    );
    $("#detailsModal").modal("show");
  });
}
// =======================
// Search Models
// =======================
function searchModels() {
  const query = $("#searchModel").val().trim();
  if (query.length < 2) return;

  $("#modelList").html(
    '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>'
  );

  $.get(
    "../api/inventory_management.php",
    {
      action: "search_inventory",
      query: query,
    },
    function (response) {
      if (response.success && Array.isArray(response.data) && response.data.length > 0) {
        let html = "<h6>Search Results</h6>";

        // ✅ group by model so one card per model
        const modelGroups = {};
        response.data.forEach((item) => {
          const modelKey = item.model?.trim() || "Unknown Model"; // normalize key
          if (!Array.isArray(modelGroups[modelKey])) {
            modelGroups[modelKey] = [];
          }
          modelGroups[modelKey].push(item);
        });

        // ✅ render each model card
        Object.keys(modelGroups).forEach((model) => {
          const items = modelGroups[model];
          const first = items[0]; // preview first unit
          html += `
            <div class="card mb-2 model-item" data-model="${model}">
              <div class="card-body">
                <h6 class="card-title">${model}</h6>
                <p class="card-text small">
                  ${first.color} · ${first.current_branch} <br>
                  ${items.length} unit(s) available
                </p>
              </div>
            </div>
          `;
        });

        $("#modelList").html(html);

        // ✅ When a model card is clicked, show all its units in modal
        $(".model-item").click(function () {
          const model = $(this).data("model");
          const items = modelGroups[model] || []; // fallback to []
          viewModelDetails(items);
        });
      } else {
        $("#modelList").html(
          '<p class="text-muted">No matching models found</p>'
        );
        $("#branchInfo").html("<h6>Search Results</h6>");
      }
    },
    "json"
  );
}


function viewModelDetails(units) {
  let html = "";

  units.forEach((data, index) => {
    html += `
      <div class="card mb-3">
        <div class="card-header">
          Unit ${index + 1}
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <h6 class="text-black">Basic Information</h6>
              <hr>
              <p><strong>Invoice Number/MT:</strong> ${data.invoice_number || "N/A"}</p>
              <p><strong>Brand:</strong> ${data.brand}</p>
              <p><strong>Model:</strong> ${data.model}</p>
              <p><strong>Color:</strong> ${data.color}</p>
              <p><strong>Current Branch:</strong> ${data.current_branch}</p>
              <p><strong>Status:</strong> 
                <span class="badge ${getStatusClass(data.status)}">
                  ${data.status.charAt(0).toUpperCase() + data.status.slice(1)}
                </span>
              </p>
            </div>
            <div class="col-md-6">
              <h6 class="text-black">Identification & Pricing</h6>
              <hr>
              <p><strong>Engine #:</strong> ${data.engine_number}</p>
              <p><strong>Frame #:</strong> ${data.frame_number}</p>
              <p><strong>Inventory Cost:</strong> ${
                data.inventory_cost ? formatCurrency(data.inventory_cost) : "N/A"
              }</p>
            </div>
          </div>
        </div>
      </div>
    `;
  });

  $("#detailsModal .modal-body").html(html);
  $("#detailsModal").modal("show");
}



$("#addMotorcycleModal").on("shown.bs.modal", function () {
  if (!isAdmin) {
    $("#branch").val(currentBranch).prop("readonly", true);
  } else {
    $("#branch").prop("readonly", false);
  }
});
$("#addMotorcycleModal").on("hidden.bs.modal", function () {
  if (!isAdmin) {
    $("#branch").val(currentBranch);
  }
});


// =======================
// Monthly Motorcycle Report
// =======================


// Show/hide brand filter based on report type selection
$('#reportType').change(function() {
    if ($(this).val() === 'motorcycle') {
        $('#brandFilterContainer').show();
    } else {
        $('#brandFilterContainer').hide();
    }
});

// Initialize the visibility on page load
$(document).ready(function() {
    if ($('#reportType').val() === 'motorcycle') {
        $('#brandFilterContainer').show();
    }
});

// Function to generate motorcycle report
function generateMotorcycleReport(branch, brandFilter) {
    $('#monthlyReportOptionsModal').modal('hide');
    $('#monthlyReportContent').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>');

    $.ajax({
        url: '../api/inventory_management.php',
        method: 'GET',
        data: {
            action: 'get_available_motorcycles_report',
            branch: branch,
            brand: brandFilter
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                currentReportData = response.data;
                currentReportType = 'motorcycle'; // Set report type
                renderMotorcycleReport(response.data, branch, brandFilter);
                $('#monthlyInventoryReportModal').modal('show');
            } else {
                showErrorModal(response.message || 'Error generating report');
            }
        },
        error: function(xhr, status, error) {
            showErrorModal('Error generating report: ' + error);
        }
    });
}

// Function to render motorcycle report
function renderMotorcycleReport(data, branch, brandFilter) {
    const timestamp = new Date().toLocaleString();
    $('#monthlyReportTimestamp').text('Generated on: ' + timestamp);
    $('#monthlyInventoryReportModalLabel').text('Available Motorcycle Units Report');
    
    let html = `
        <div class="report-header text-center mb-4">
            <div class="d-flex align-items-center justify-content-center mb-2">
                <div style="width: 40px; height: 2px; background: #000f71; margin-right: 15px;"></div>
                <h4 class="mb-0" style="color: #000f71; font-weight: 600; letter-spacing: 0.5px;">
                    SOLID MOTORCYCLE DISTRIBUTORS, INC.
                </h4>
                <div style="width: 40px; height: 2px; background: #000f71; margin-left: 15px;"></div>
            </div>
            <h5 class="mb-2" style="color: #495057; font-weight: 500;">AVAILABLE MOTORCYCLE UNITS REPORT</h5>
            <p class="text-muted">
                ${brandFilter === 'all' ? 'ALL BRANDS' : brandFilter.toUpperCase()} | 
                ${branch ? branch : 'ALL BRANCHES'}
            </p>
            <p class="text-muted small mb-0" style="font-size: 0.85rem;">
                Generated on ${new Date().toLocaleDateString("en-US", {
                    weekday: "long",
                    year: "numeric",
                    month: "long",
                    day: "numeric",
                })}
            </p>
        </div>
    `;
    
    if (data.length === 0) {
        html += `
            <div class="alert alert-info text-center">
                No available motorcycles found for the selected criteria.
            </div>
        `;
    } else {
        // Group by branch
        const branches = {};
        data.forEach(item => {
            if (!branches[item.current_branch]) {
                branches[item.current_branch] = [];
            }
            branches[item.current_branch].push(item);
        });
        
        // Create report sections for each branch
        Object.keys(branches).forEach(branch => {
            const branchData = branches[branch];
            
            html += `
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">${branch} - ${branchData.length} units</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-container" style="border: 1px solid #e9ecef; border-radius: 6px; max-height: 60vh; overflow-y: auto;">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr style="background-color: #f8f9fa; border-bottom: 2px solid #dee2e6; position: sticky; top: 0; z-index: 10;">
                                        <th class="text-center py-3" style="font-weight: 600; color: #495057; width: 60px;">QTY</th>
                                        <th class="py-3" style="font-weight: 600; color: #495057;">MODEL</th>
                                        <th class="py-3" style="font-weight: 600; color: #495057;">COLOR</th>
                                        <th class="py-3" style="font-weight: 600; color: #495057;">BRAND</th>
                                        <th class="py-3" style="font-weight: 600; color: #495057;">ENGINE NUMBER</th>
                                        <th class="py-3" style="font-weight: 600; color: #495057;">FRAME NUMBER</th>
                                        <th class="py-3" style="font-weight: 600; color: #495057;">Inventory Cost</th>
                                    </tr>
                                </thead>
                                <tbody>
            `;
            
            branchData.forEach(item => {
                html += `
                    <tr>
                        <td class="text-center py-2" style="border-right: 1px solid #e9ecef;">1</td>
                        <td class="py-2" style="border-right: 1px solid #e9ecef;">${escapeHtml(item.model)}</td>
                        <td class="py-2" style="border-right: 1px solid #e9ecef;">${escapeHtml(item.color)}</td>
                        <td class="py-2" style="border-right: 1px solid #e9ecef;">${escapeHtml(item.brand)}</td>
                        <td class="py-2">${escapeHtml(item.engine_number)}</td>
                        <td class="py-2">${escapeHtml(item.frame_number)}</td>
                        <td class="py-2 text-end">${formatCurrency(item.inventory_cost)}</td>
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
        });
        
        // Add summary
        const totalUnits = data.length;
        const totalValue = data.reduce((sum, item) => sum + (parseFloat(item.inventory_cost) || 0), 0);
        
        html += `
            <div class="alert alert-primary mt-3">
                <div class="row">
                    <div class="col-md-6">
                        <strong>Total Units:</strong> ${totalUnits}
                    </div>
                    <div class="col-md-6 text-end">
                        <strong>Total Inventory Value:</strong> ${formatCurrency(totalValue)}
                    </div>
                </div>
            </div>
        `;
    }
    
    $('#monthlyReportContent').html(html);
    
    // Add styling for the modal
    $("<style>")
        .prop("type", "text/css")
        .html(`
            #monthlyInventoryReportModal .modal-body {
                max-height: calc(100vh - 200px);
                overflow-y: auto;
            }
            #monthlyInventoryReportModal .modal-dialog {
                max-width: 95%;
                height: calc(100vh - 100px);
            }
            #monthlyInventoryReportModal .modal-content {
                height: 100%;
            }
            .table-container { overflow: hidden; }
            .table th { font-weight: 600; font-size: 0.9rem; }
            .table td { font-size: 0.9rem; color: #495057; }
            .card { box-shadow: 0 4px 6px rgba(0, 0, 0, 0.04); }
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
            .modal-body { max-height: calc(100vh - 200px); overflow-y: auto; }
            .table-container thead th { position: sticky; top: 0; background-color: #f8f9fa; z-index: 10; }
        `)
        .appendTo("head");
    
    // Update export button for motorcycle report
    $('#exportMonthlyReportToPDF').off('click').on('click', function() {
        exportMotorcycleReportToPDF(html, branch, brandFilter);
    });
}

function exportMotorcycleReportToPDF(html, branch, brandFilter) {
    const printWindow = window.open('', '_blank');
    const title = `Motorcycle_Inventory_Report_${brandFilter}_${branch || 'all'}_${new Date().toISOString().slice(0, 10)}`;
    
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>${title}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .report-header { text-align: center; margin-bottom: 20px; }
                .report-header h4 { color: #000f71; font-weight: 600; }
                .report-header h5 { color: #495057; font-weight: 500; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f8f9fa; font-weight: 600; }
                .summary { margin-top: 20px; padding: 15px; background-color: #e9ecef; border-radius: 5px; }
                .card { margin-bottom: 20px; border: 1px solid #e9ecef; border-radius: 6px; }
                .card-header { background-color: #f8f9fa; padding: 10px; border-bottom: 1px solid #e9ecef; }
            </style>
        </head>
        <body>
            ${html}
        </body>
        </html>
    `);
    
    printWindow.document.close();
    printWindow.focus();
    setTimeout(function() {
        printWindow.print();
        // printWindow.close(); // Uncomment if you want to automatically close after printing
    }, 250);
}
// =======================
// Monthly Inventory Report
// =======================
function showMonthlyInventoryOptions() {
  if ($("#reportBranch option").length <= 1) {
    populateBranchesDropdown();
  }

  const now = new Date();
  const currentMonth =
    now.getFullYear() + "-" + String(now.getMonth() + 1).padStart(2, "0");
  $("#selectedMonth").val(currentMonth);

  $("#monthlyInventoryOptionsModal").modal("show");
}

function toggleReportOptions() {
  const reportType = $("#reportPeriod").val();
  if (reportType === "month") {
    $("#monthSelection").removeClass("d-none");
    $("#branchSelection").addClass("d-none");
  } else {
    $("#monthSelection").addClass("d-none");
    $("#branchSelection").removeClass("d-none");
  }
}
function populateBranchesDropdown() {
  const branches = [
    "HEADOFFICE",
    "KINGDOM",
    "TANQUE",
    "ROXAS SUZUKI",
    "MAMBUSAO",
    "SIGMA",
    "PRC",
    "BAILAN",
    "CUARTERO",
    "JAMINDAN",
    "ROXAS HONDA",
    "ANTIQUE-1",
    "ANTIQUE-2",
    "DELGADO HONDA",
    "DELGADO SUZUKI",
    "JARO-1",
    "JARO-2",
    "KALIBO MABINI",
    "KALIBO SUZUKI",
    "ALTAVAS",
    "EMAP",
    "CULASI",
    "BACOLOD",
    "PASSI-1",
    "PASSI-2",
    "BALASAN",
    "GUIMARAS",
    "PEMDI",
    "EEMSI",
    "AJUY",
    "MINDORO ROXAS",
    "3S MINDORO",
    "MINDORO MANSALAY",
    "K-RIDERS ROXAS",
    "IBAJAY",
    "NUMANCIA"
  ];

  const $dropdown = $("#reportBranch");
  branches.forEach((branch) => {
    $dropdown.append(`<option value="${branch}">${branch}</option>`);
  });
}

// Update the modal title when showing inventory reports
function generateMonthlyInventoryReport(month, branch) {
    $("#monthlyInventoryOptionsModal").modal("hide");
    $("#monthlyReportContent").html(
        '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>'
    );

    $.ajax({
        url: "../api/inventory_management.php",
        method: "GET",
        data: {
            action: "get_monthly_inventory",
            month: month,
            branch: branch || "all",
        },
        dataType: "json",
        success: function (response) {
            if (response.success) {
                currentReportData = response.data;
                currentReportMonth = response.month;
                currentReportBranch = response.branch;
                currentReportSummary = response.summary;
                currentReportType = 'inventory';
                
                // ✅ Update modal title to reflect beginning balance functionality
                $("#monthlyInventoryReportModalLabel").text('Monthly Inventory Balance Report (with Beginning Balance)');
                
                renderMonthlyInventoryReport(
                    response.data,
                    response.month,
                    response.branch,
                    response.summary
                );
                $("#monthlyInventoryReportModal").modal("show");
            } else {
                showErrorModal(response.message || "Error generating report");
            }
        },
        error: function (xhr, status, error) {
            showErrorModal("Error generating report: " + error);
        }
    });
}



function renderMonthlyInventoryReport(data, month, branch, summary) {
  const [year, monthNum] = month.split("-");
  const monthName = new Date(year, monthNum - 1, 1).toLocaleString("default", {
    month: "long",
  });
  const branchName = branch === "all" ? "All Branches" : branch;

  // Sort data by model for cleaner display
  data.sort((a, b) => a.model.localeCompare(b.model));

const beginningBalance = currentReportSummary?.beginning_balance || 0;
    const receivedTransfers = currentReportSummary?.received_transfers || 0;
    const newDeliveries = currentReportSummary?.new_deliveries || 0;
    const totalIn = currentReportSummary?.in || 0; // Changed from total_in to in
    const transfersOut = currentReportSummary?.transfers_out || 0;
    const soldDuringMonth = currentReportSummary?.sold_during_month || 0;
    const totalOut = currentReportSummary?.out || 0; // Changed from total_out to out
    const endingCalculated = currentReportSummary?.ending_calculated || 0;
    const endingActual = currentReportSummary?.ending_actual || 0;
    
    // Inventory cost values
    const costBeginning = currentReportSummary?.inventory_cost?.beginning_balance || 0;
    const costReceived = currentReportSummary?.inventory_cost?.received_transfers || 0;
    const costNewDeliveries = currentReportSummary?.inventory_cost?.new_deliveries || 0;
    const costTotalIn = currentReportSummary?.inventory_cost?.in || 0; // Changed from total_in to in
    const costTransfersOut = currentReportSummary?.inventory_cost?.transfers_out || 0;
    const costSoldDuringMonth = currentReportSummary?.inventory_cost?.sold_during_month || 0;
    const costTotalOut = currentReportSummary?.inventory_cost?.out || 0; // Changed from total_out to out
    const costEndingCalculated = currentReportSummary?.inventory_cost?.ending_calculated || 0;
    const costEndingActual = currentReportSummary?.inventory_cost?.ending_actual || 0;

  let html = `
    <div class="report-header text-center mb-4">
      <div class="d-flex align-items-center justify-content-center mb-2">
        <div style="width: 40px; height: 2px; background: #000f71; margin-right: 15px;"></div>
        <h4 class="mb-0" style="color: #000f71; font-weight: 600; letter-spacing: 0.5px;">
          SOLID MOTORCYCLE DISTRIBUTORS, INC.
        </h4>
        <div style="width: 40px; height: 2px; background: #000f71; margin-left: 15px;"></div>
      </div>
      <h5 class="mb-2" style="color: #495057; font-weight: 500;">MONTHLY INVENTORY BALANCE REPORT</h5>
      <h6 class="mb-2 text-muted" style="font-weight: 400;">${monthName} ${year}</h6>
      ${
        branch !== "all"
          ? `<p class="mb-1"><span style="color: #6c757d;">Branch:</span> 
             <span style="color: #000f71; font-weight: 500;">${branchName}</span></p>`
          : ""
      }
      <p class="text-muted small mb-0" style="font-size: 0.85rem;">
        Generated on ${new Date().toLocaleDateString("en-US", {
          weekday: "long",
          year: "numeric",
          month: "long",
          day: "numeric",
        })}
      </p>
    </div>
    
    <div class="row">
      <div class="col-md-8">
        <div class="table-container" style="border: 1px solid #e9ecef; border-radius: 6px; max-height: 60vh; overflow-y: auto;">
          <table class="table table-sm mb-0">
            <thead>
              <tr style="background-color: #f8f9fa; border-bottom: 2px solid #dee2e6; position: sticky; top: 0; z-index: 10;">
                <th class="text-center py-3" style="font-weight: 600; color: #495057; width: 60px;">QTY</th>
                <th class="py-3" style="font-weight: 600; color: #495057;">MODEL</th>
                <th class="py-3" style="font-weight: 600; color: #495057;">COLOR</th>
                <th class="py-3" style="font-weight: 600; color: #495057;">BRAND</th>
                <th class="py-3" style="font-weight: 600; color: #495057;">ENGINE NUMBER</th>
                <th class="py-3" style="font-weight: 600; color: #495057;">FRAME NUMBER</th>
                <th class="py-3" style="font-weight: 600; color: #495057;">Inventory Cost</th>
              </tr>
            </thead>
            <tbody>
  `;

  if (data.length === 0) {
    html += `
      <tr>
        <td colspan="7" class="text-center py-5 text-muted" style="font-style: italic;">
          No inventory data found for this period
        </td>
      </tr>
    `;
  } else {
    data.forEach((item, index) => {
      const rowClass = index % 2 === 0 ? "bg-white" : "bg-light";
      html += `
        <tr class="${rowClass}">
          <td class="text-center py-2" style="border-right: 1px solid #e9ecef;">1</td>
          <td class="py-2" style="border-right: 1px solid #e9ecef;">${escapeHtml(item.model)}</td>
          <td class="py-2" style="border-right: 1px solid #e9ecef;">${escapeHtml(item.color)}</td>
          <td class="py-2" style="border-right: 1px solid #e9ecef;">${escapeHtml(item.brand)}</td>
          <td class="py-2">${escapeHtml(item.engine_number)}</td>
          <td class="py-2">${escapeHtml(item.frame_number)}</td>
          <td class="py-2 text-end">${formatCurrency(item.inventory_cost)}</td>
        </tr>
      `;
    });
  }

  html += `
            </tbody>
          </table>
        </div>
      </div>
      
      <div class="col-md-4">
        <div class="summary-section" style="position: sticky; top: 20px;">
          <!-- Beginning Balance Card -->
          <div class="card border-0 shadow-sm mb-3" style="border-radius: 8px;">
            <div class="card-header bg-transparent border-0 pt-3 pb-2">
              <h6 class="card-title text-center mb-0" style="color: #6c757d; font-weight: 600; font-size: 0.9rem;">
                BEGINNING BALANCE
              </h6>
            </div>
            <div class="card-body px-4 pb-3 pt-0">
              <div class="text-center">
                <span class="fs-4 fw-bold" style="color: #6c757d;">${beginningBalance}</span>
                <div class="small text-muted mt-1">${formatCurrency(costBeginning)}</div>
              </div>
            </div>
          </div>

          <!-- IN Section -->
          <div class="card border-0 shadow-sm mb-3" style="border-radius: 8px;">
            <div class="card-header bg-transparent border-0 pt-3 pb-2">
              <h6 class="card-title text-center mb-0" style="color: #28a745; font-weight: 600; font-size: 0.9rem;">
                INVENTORY IN
              </h6>
            </div>
            <div class="card-body px-4 pb-3 pt-0">
              <div class="summary-item d-flex justify-content-between align-items-center mb-2 pb-2" style="border-bottom: 1px solid #f1f3f4;">
                <div>
                  <div class="fw-semibold small" style="color: #495057;">Received Transfers</div>
                </div>
                <div class="text-end">
                  <span class="fw-bold" style="color: #28a745;">${receivedTransfers}</span>
                  <div class="small text-muted">${formatCurrency(costReceived)}</div>
                </div>
              </div>
              
              <div class="summary-item d-flex justify-content-between align-items-center mb-2 pb-2" style="border-bottom: 1px solid #f1f3f4;">
                <div>
                  <div class="fw-semibold small" style="color: #495057;">New Deliveries</div>
                </div>
                <div class="text-end">
                  <span class="fw-bold" style="color: #28a745;">${newDeliveries}</span>
                  <div class="small text-muted">${formatCurrency(costNewDeliveries)}</div>
                </div>
              </div>
              
              <div class="summary-item d-flex justify-content-between align-items-center pt-2">
                <div>
                  <div class="fw-bold" style="color: #28a745;">TOTAL IN</div>
                </div>
                <div class="text-end">
                  <span class="fs-5 fw-bold" style="color: #28a745;">${totalIn}</span>
                  <div class="small text-success fw-bold">${formatCurrency(costTotalIn)}</div>
                </div>
              </div>
            </div>
          </div>

          <!-- OUT Section -->
          <div class="card border-0 shadow-sm mb-3" style="border-radius: 8px;">
            <div class="card-header bg-transparent border-0 pt-3 pb-2">
              <h6 class="card-title text-center mb-0" style="color: #dc3545; font-weight: 600; font-size: 0.9rem;">
                INVENTORY OUT
              </h6>
            </div>
            <div class="card-body px-4 pb-3 pt-0">
              <div class="summary-item d-flex justify-content-between align-items-center mb-2 pb-2" style="border-bottom: 1px solid #f1f3f4;">
                <div>
                  <div class="fw-semibold small" style="color: #495057;">Transfers Out</div>
                </div>
                <div class="text-end">
                  <span class="fw-bold" style="color: #dc3545;">${transfersOut}</span>
                  <div class="small text-muted">${formatCurrency(costTransfersOut)}</div>
                </div>
              </div>
              
              <div class="summary-item d-flex justify-content-between align-items-center mb-2 pb-2" style="border-bottom: 1px solid #f1f3f4;">
                <div>
                  <div class="fw-semibold small" style="color: #495057;">Sold During Month</div>
                </div>
                <div class="text-end">
                  <span class="fw-bold" style="color: #dc3545;">${soldDuringMonth}</span>
                  <div class="small text-muted">${formatCurrency(costSoldDuringMonth)}</div>
                </div>
              </div>
              
              <div class="summary-item d-flex justify-content-between align-items-center pt-2">
                <div>
                  <div class="fw-bold" style="color: #dc3545;">TOTAL OUT</div>
                </div>
                <div class="text-end">
                  <span class="fs-5 fw-bold" style="color: #dc3545;">${totalOut}</span>
                  <div class="small text-danger fw-bold">${formatCurrency(costTotalOut)}</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Ending Balance Card -->
          <div class="card border-0 shadow-sm mb-3" style="border-radius: 8px;">
            <div class="card-header bg-transparent border-0 pt-3 pb-2">
              <h6 class="card-title text-center mb-0" style="color: #000f71; font-weight: 600; font-size: 0.9rem;">
                ENDING BALANCE
              </h6>
            </div>
            <div class="card-body px-4 pb-3 pt-0">
              <div class="summary-item d-flex justify-content-between align-items-center mb-2 pb-2" style="border-bottom: 1px solid #f1f3f4;">
                <div>
                  <div class="fw-semibold small" style="color: #495057;">Calculated</div>
                </div>
                <div class="text-end">
                  <span class="fw-bold" style="color: #000f71;">${endingCalculated}</span>
                  <div class="small text-muted">${formatCurrency(costEndingCalculated)}</div>
                </div>
              </div>
              
              <div class="summary-item d-flex justify-content-between align-items-center pt-2">
                <div>
                  <div class="fw-bold" style="color: #000f71;">Actual</div>
                </div>
                <div class="text-end">
                  <span class="fs-4 fw-bold" style="color: #000f71;">${endingActual}</span>
                  <div class="small text-primary fw-bold">${formatCurrency(costEndingActual)}</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Discrepancy Alert (if any) -->
          ${endingCalculated !== endingActual ? `
          <div class="alert alert-warning alert-sm">
            <div class="d-flex justify-content-between">
              <small><strong>Discrepancy:</strong></small>
              <small><strong>${endingActual - endingCalculated}</strong></small>
            </div>
          </div>
          ` : ''}
        </div>
      </div>
    </div>
    
    <style>
      .table-container { overflow: hidden; }
      .table th { font-weight: 600; font-size: 0.9rem; }
      .table td { font-size: 0.9rem; color: #495057; }
      .card { box-shadow: 0 4px 6px rgba(0, 0, 0, 0.04); }
      body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
      .modal-body { max-height: calc(100vh - 200px); overflow-y: auto; }
      .table-container thead th { position: sticky; top: 0; background-color: #f8f9fa; z-index: 10; }
    </style>
  `;

  $("#monthlyReportContent").html(html);

  $("<style>")
    .prop("type", "text/css")
    .html(`
      #monthlyInventoryReportModal .modal-body {
        max-height: calc(100vh - 200px);
                overflow-y: auto;
      }
      #monthlyInventoryReportModal .modal-dialog {
        max-width: 95%;
        height: calc(100vh - 100px);
      }
      #monthlyInventoryReportModal .modal-content {
        height: 100%;
      }
    `)
    .appendTo("head");
}


document.addEventListener("DOMContentLoaded", function () {
  const loggedInBranch = currentUserBranch;
  document.getElementById("reportBranch").value = loggedInBranch;
  document.getElementById("reportBranch").setAttribute("disabled", true);
});




function exportMonthlyReportToPDF() {
  const reportEl = document.getElementById("monthlyReportPrintContainer");

  if (!reportEl || !reportEl.innerHTML.trim()) {
    alert("No report content available to export.");
    return;
  }

  reportEl.style.display = "block";

  const opt = {
    margin: 0.5,
    filename: `Monthly_Inventory_Report_${new Date()
      .toISOString()
      .slice(0, 10)}.pdf`,
    image: { type: "jpeg", quality: 0.98 },
    html2canvas: { scale: 2, useCORS: true },
    jsPDF: { unit: "in", format: "letter", orientation: "portrait" },
  };

  html2pdf()
    .set(opt)
    .from(reportEl)
    .save()
    .then(() => {
      reportEl.style.display = "none";
    });
}

function exportMonthlyReport() {
  let csvContent = "data:text/csv;charset=utf-8,";

  const headers = [];
  $("#monthlyReportContent thead th").each(function () {
    headers.push($(this).text().trim());
  });
  csvContent += headers.join(",") + "\n";

  $("#monthlyReportContent tbody tr").each(function () {
    const row = [];
    $(this)
      .find("td")
      .each(function () {
        row.push($(this).text().trim());
      });
    csvContent += row.join(",") + "\n";
  });

  const encodedUri = encodeURI(csvContent);
  const link = document.createElement("a");
  link.setAttribute("href", encodedUri);
  link.setAttribute(
    "download",
    $("#monthlyInventoryReportModalLabel")
      .text()
      .toLowerCase()
      .replace(/ /g, "_") + ".csv"
  );
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}
// =======================
// Report Generation Functions
// =======================
function showMonthlyReportOptions() {
    // Get the current user's branch and position
    const userBranch = currentUserBranch;
    const userPosition = currentUserPosition;
    const isAdminOrHeadOffice = isAdminUser || isHeadOffice;

    // Set the current month as default
    const now = new Date();
    const currentMonth = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');
    $('#reportMonth').val(currentMonth);

    // Handle branch selection based on user role
    if (isAdminOrHeadOffice) {
        // Admin/HeadOffice can select any branch including ALL BRANCHES
        populateBranchesDropdown();
        $('#reportBranch').prop('disabled', false);
    } else {
        // Regular users - auto-populate with their branch and disable selection
        $('#reportBranch').empty().append(`<option value="${userBranch}">${userBranch}</option>`);
        $('#reportBranch').val(userBranch).prop('disabled', true);
    }

    // Show the modal
    $('#monthlyReportOptionsModal').modal('show');
}



function populateBranchesDropdown() {
    const branches = [
        'ALL',"HEADOFFICE",
    "KINGDOM",
    "TANQUE",
    "ROXAS SUZUKI",
    "MAMBUSAO",
    "SIGMA",
    "PRC",
    "BAILAN",
    "CUARTERO",
    "JAMINDAN",
    "ROXAS HONDA",
    "ANTIQUE-1",
    "ANTIQUE-2",
    "DELGADO HONDA",
    "DELGADO SUZUKI",
    "JARO-1",
    "JARO-2",
    "KALIBO MABINI",
    "KALIBO SUZUKI",
    "ALTAVAS",
    "EMAP",
    "CULASI",
    "BACOLOD",
    "PASSI-1",
    "PASSI-2",
    "BALASAN",
    "GUIMARAS",
    "PEMDI",
    "EEMSI",
    "AJUY",
    "MINDORO ROXAS",
    "3S MINDORO",
    "MINDORO MANSALAY",
    "K-RIDERS ROXAS",
    "IBAJAY",
    "NUMANCIA"
    ];

    const $dropdown = $('#reportBranch');
    $dropdown.empty().append('<option value="">Select Branch</option>');
    
    branches.forEach(branch => {
        $dropdown.append(`<option value="${branch}">${branch}</option>`);
    });
}
function generateReport() {
    const month = $('#reportMonth').val();
    const branch = $('#reportBranch').val();
    const reportType = $('#reportType').val();

    // For non-admin users, use their branch
    const reportBranch = branch === 'ALL' ? 'all' : (branch || currentUserBranch);

    $('#monthlyReportOptionsModal').modal('hide');
    $('#monthlyReportContent').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>');

    if (reportType === 'inventory') {
        // Validate month selection for inventory reports
        if (!month) {
            showErrorModal("Please select a month.");
            return;
        }
        generateMonthlyInventoryReport(month, reportBranch);
    } else if (reportType === 'transferred') {
        // Validate month selection for transferred reports
        if (!month) {
            showErrorModal("Please select a month.");
            return;
        }
        generateTransferredSummary(month, reportBranch);
    } else if (reportType === 'motorcycle') {
        // No month validation needed for motorcycle report
        const brandFilter = $('#reportBrandFilter').val();
        generateMotorcycleReport(reportBranch, brandFilter);
    }
}
function generateReportPDF() {
    if (!currentReportData || !currentReportType) {
        showErrorModal("Please generate a report first before exporting to PDF");
        return;
    }

    if (currentReportType === 'inventory') {
        generateInventoryReportPDF();
    } else if (currentReportType === 'transferred') {
        generateTransferredReportPDF();
    } else if (currentReportType === 'motorcycle') {
        generateMotorcycleReportPDF();
    }
}
function generateInventoryReportPDF() {
    const [year, monthNum] = currentReportMonth.split("-");
    const monthName = new Date(year, monthNum - 1, 1).toLocaleString("default", {
        month: "long",
    });
    const branchName = currentReportBranch === "all" ? "All Branches" : currentReportBranch;

    // Use same variable names as renderMonthlyInventoryReport
    const beginningBalance = currentReportSummary?.beginning_balance || 0;
    const receivedTransfers = currentReportSummary?.received_transfers || 0;
    const newDeliveries = currentReportSummary?.new_deliveries || 0;
    const totalIn = currentReportSummary?.in || 0;
    const transfersOut = currentReportSummary?.transfers_out || 0;
    const soldDuringMonth = currentReportSummary?.sold_during_month || 0;
    const totalOut = currentReportSummary?.out || 0;
    const endingCalculated = currentReportSummary?.ending_calculated || 0;
    const endingActual = currentReportSummary?.ending_actual || 0;
    
    // Inventory cost values
    const costBeginning = currentReportSummary?.inventory_cost?.beginning_balance || 0;
    const costReceived = currentReportSummary?.inventory_cost?.received_transfers || 0;
    const costNewDeliveries = currentReportSummary?.inventory_cost?.new_deliveries || 0;
    const costTotalIn = currentReportSummary?.inventory_cost?.in || 0;
    const costTransfersOut = currentReportSummary?.inventory_cost?.transfers_out || 0;
    const costSoldDuringMonth = currentReportSummary?.inventory_cost?.sold_during_month || 0;
    const costTotalOut = currentReportSummary?.inventory_cost?.out || 0;
    const costEndingCalculated = currentReportSummary?.inventory_cost?.ending_calculated || 0;
    const costEndingActual = currentReportSummary?.inventory_cost?.ending_actual || 0;

    // Sort data by model for cleaner display (same as render function)
    currentReportData.sort((a, b) => a.model.localeCompare(b.model));

    const rowsHtml = currentReportData.length === 0 ? `
        <tr>
            <td colspan="7" style="text-align: center; padding: 30px; color: #6c757d; font-style: italic;">
                No inventory data found for this period
            </td>
        </tr>
    ` : currentReportData.map((item, index) => {
        const rowBg = index % 2 === 0 ? "#ffffff" : "#f8f9fa";
        return `
            <tr style="background-color: ${rowBg};">
                <td style="text-align: center; padding: 8px; border-right: 1px solid #e9ecef;">1</td>
                <td style="padding: 8px; border-right: 1px solid #e9ecef;">${escapeHtml(item.model)}</td>
                <td style="padding: 8px; border-right: 1px solid #e9ecef;">${escapeHtml(item.color)}</td>
                <td style="padding: 8px; border-right: 1px solid #e9ecef;">${escapeHtml(item.brand)}</td>
                <td style="padding: 8px; border-right: 1px solid #e9ecef;">${escapeHtml(item.engine_number)}</td>
                <td style="padding: 8px; border-right: 1px solid #e9ecef;">${escapeHtml(item.frame_number)}</td>
                <td style="padding: 8px; text-align: right;">${formatCurrency(item.inventory_cost)}</td>
            </tr>
        `;
    }).join("");

    const html = `
        <div style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px;">
            <!-- Header Section - Same as render function -->
            <div style="text-align: center; margin-bottom: 30px;">
                <div style="display: flex; align-items: center; justify-content: center; margin-bottom: 15px;">
                    <div style="width: 40px; height: 2px; background: #000f71; margin-right: 15px;"></div>
                    <h4 style="margin: 0; color: #000f71; font-weight: 600; letter-spacing: 0.5px;">
                        SOLID MOTORCYCLE DISTRIBUTORS, INC.
                    </h4>
                    <div style="width: 40px; height: 2px; background: #000f71; margin-left: 15px;"></div>
                </div>
                <h5 style="margin: 10px 0; color: #495057; font-weight: 500;">MONTHLY INVENTORY BALANCE REPORT</h5>
                <h6 style="margin: 5px 0; color: #6c757d; font-weight: 400;">${monthName} ${year}</h6>
                ${currentReportBranch !== "all" ? `
                    <p style="margin: 5px 0;">
                        <span style="color: #6c757d;">Branch:</span> 
                        <span style="color: #000f71; font-weight: 500;">${branchName}</span>
                    </p>
                ` : ""}
                <p style="color: #6c757d; font-size: 12px; margin: 5px 0;">
                    Generated on ${new Date().toLocaleDateString("en-US", {
                        weekday: "long",
                        year: "numeric",
                        month: "long",
                        day: "numeric",
                    })}
                </p>
            </div>
            
            <!-- Main Content Layout - Two Column like render function -->
            <div style="display: flex; gap: 20px;">
                <!-- Left Column - Table (70% width) -->
                <div style="flex: 0 0 70%; border: 1px solid #e9ecef; border-radius: 6px;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
                        <thead>
                            <tr style="background-color: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                                <th style="text-align: center; padding: 12px; font-weight: 600; color: #495057; width: 60px;">QTY</th>
                                <th style="padding: 12px; font-weight: 600; color: #495057;">MODEL</th>
                                <th style="padding: 12px; font-weight: 600; color: #495057;">COLOR</th>
                                <th style="padding: 12px; font-weight: 600; color: #495057;">BRAND</th>
                                <th style="padding: 12px; font-weight: 600; color: #495057;">ENGINE NUMBER</th>
                                <th style="padding: 12px; font-weight: 600; color: #495057;">FRAME NUMBER</th>
                                <th style="padding: 12px; font-weight: 600; color: #495057;">Inventory Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${rowsHtml}
                        </tbody>
                    </table>
                </div>
                
                <!-- Right Column - Summary Cards (30% width) -->
                <div style="flex: 0 0 30%;">
                    <!-- Beginning Balance Card -->
                    <div style="border: 1px solid #e9ecef; border-radius: 8px; margin-bottom: 15px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.04);">
                        <div style="background: transparent; border-bottom: 1px solid #e9ecef; padding: 12px 16px 8px;">
                            <h6 style="text-align: center; margin: 0; color: #6c757d; font-weight: 600; font-size: 14px;">
                                BEGINNING BALANCE
                            </h6>
                        </div>
                        <div style="padding: 0 16px 12px;">
                            <div style="text-align: center;">
                                <span style="font-size: 24px; font-weight: bold; color: #6c757d;">${beginningBalance}</span>
                                <div style="font-size: 12px; color: #6c757d; margin-top: 4px;">${formatCurrency(costBeginning)}</div>
                            </div>
                        </div>
                    </div>

                    <!-- IN Section Card -->
                    <div style="border: 1px solid #e9ecef; border-radius: 8px; margin-bottom: 15px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.04);">
                        <div style="background: transparent; border-bottom: 1px solid #e9ecef; padding: 12px 16px 8px;">
                            <h6 style="text-align: center; margin: 0; color: #28a745; font-weight: 600; font-size: 14px;">
                                INVENTORY IN
                            </h6>
                        </div>
                        <div style="padding: 0 16px 12px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px solid #f1f3f4;">
                                <div>
                                    <div style="font-weight: 600; font-size: 12px; color: #495057;">Received Transfers</div>
                                </div>
                                <div style="text-align: right;">
                                    <span style="font-weight: bold; color: #28a745;">${receivedTransfers}</span>
                                    <div style="font-size: 11px; color: #6c757d;">${formatCurrency(costReceived)}</div>
                                </div>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px solid #f1f3f4;">
                                <div>
                                    <div style="font-weight: 600; font-size: 12px; color: #495057;">New Deliveries</div>
                                </div>
                                <div style="text-align: right;">
                                    <span style="font-weight: bold; color: #28a745;">${newDeliveries}</span>
                                    <div style="font-size: 11px; color: #6c757d;">${formatCurrency(costNewDeliveries)}</div>
                                </div>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 8px;">
                                <div>
                                    <div style="font-weight: bold; color: #28a745;">TOTAL IN</div>
                                </div>
                                <div style="text-align: right;">
                                    <span style="font-size: 18px; font-weight: bold; color: #28a745;">${totalIn}</span>
                                    <div style="font-size: 11px; color: #28a745; font-weight: bold;">${formatCurrency(costTotalIn)}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- OUT Section Card -->
                    <div style="border: 1px solid #e9ecef; border-radius: 8px; margin-bottom: 15px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.04);">
                        <div style="background: transparent; border-bottom: 1px solid #e9ecef; padding: 12px 16px 8px;">
                            <h6 style="text-align: center; margin: 0; color: #dc3545; font-weight: 600; font-size: 14px;">
                                INVENTORY OUT
                            </h6>
                        </div>
                        <div style="padding: 0 16px 12px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px solid #f1f3f4;">
                                <div>
                                    <div style="font-weight: 600; font-size: 12px; color: #495057;">Transfers Out</div>
                                </div>
                                <div style="text-align: right;">
                                    <span style="font-weight: bold; color: #dc3545;">${transfersOut}</span>
                                    <div style="font-size: 11px; color: #6c757d;">${formatCurrency(costTransfersOut)}</div>
                                </div>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px solid #f1f3f4;">
                                <div>
                                    <div style="font-weight: 600; font-size: 12px; color: #495057;">Sold During Month</div>
                                </div>
                                <div style="text-align: right;">
                                    <span style="font-weight: bold; color: #dc3545;">${soldDuringMonth}</span>
                                    <div style="font-size: 11px; color: #6c757d;">${formatCurrency(costSoldDuringMonth)}</div>
                                </div>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 8px;">
                                <div>
                                    <div style="font-weight: bold; color: #dc3545;">TOTAL OUT</div>
                                </div>
                                <div style="text-align: right;">
                                    <span style="font-size: 18px; font-weight: bold; color: #dc3545;">${totalOut}</span>
                                    <div style="font-size: 11px; color: #dc3545; font-weight: bold;">${formatCurrency(costTotalOut)}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ending Balance Card -->
                    <div style="border: 1px solid #e9ecef; border-radius: 8px; margin-bottom: 15px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.04);">
                        <div style="background: transparent; border-bottom: 1px solid #e9ecef; padding: 12px 16px 8px;">
                            <h6 style="text-align: center; margin: 0; color: #000f71; font-weight: 600; font-size: 14px;">
                                ENDING BALANCE
                            </h6>
                        </div>
                        <div style="padding: 0 16px 12px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px solid #f1f3f4;">
                                <div>
                                    <div style="font-weight: 600; font-size: 12px; color: #495057;">Calculated</div>
                                </div>
                                <div style="text-align: right;">
                                    <span style="font-weight: bold; color: #000f71;">${endingCalculated}</span>
                                    <div style="font-size: 11px; color: #6c757d;">${formatCurrency(costEndingCalculated)}</div>
                                </div>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 8px;">
                                <div>
                                    <div style="font-weight: bold; color: #000f71;">Actual</div>
                                </div>
                                <div style="text-align: right;">
                                    <span style="font-size: 24px; font-weight: bold; color: #000f71;">${endingActual}</span>
                                    <div style="font-size: 11px; color: #000f71; font-weight: bold;">${formatCurrency(costEndingActual)}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Discrepancy Alert (if any) -->
                    ${endingCalculated !== endingActual ? `
                    <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 10px; margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; font-size: 12px;">
                            <span style="font-weight: bold; color: #856404;">Discrepancy:</span>
                            <span style="font-weight: bold; color: #856404;">${endingActual - endingCalculated}</span>
                        </div>
                    </div>
                    ` : ''}
                </div>
            </div>
            
            <!-- Formula Explanation Section -->
            <div style="margin-top: 30px; padding: 15px; border: 1px solid #e9ecef; border-radius: 8px; background: #f8f9fa;">
                <div style="font-weight: 600; color: #495057; margin-bottom: 10px; text-align: center; font-size: 16px;">
                    INVENTORY CALCULATION FORMULA
                </div>
                <div style="text-align: center; font-size: 14px; color: #6c757d; margin-bottom: 8px;">
                    Beginning Balance + Total IN - Total OUT = Ending Balance
                </div>
                <div style="text-align: center; font-size: 13px; color: #495057; margin-bottom: 5px;">
                    ${beginningBalance} + ${totalIn} - ${totalOut} = ${endingCalculated}
                </div>
                <div style="text-align: center; font-size: 12px; color: #6c757d;">
                    Detailed: ${beginningBalance} + (${receivedTransfers} received + ${newDeliveries} new) - (${transfersOut} transferred + ${soldDuringMonth} sold) = ${endingCalculated}
                </div>
            </div>
        </div>
    `;

    const container = document.createElement("div");
    container.innerHTML = html;

    const opt = {
        margin: 0.5,
        filename: `Monthly_Inventory_Report_${currentReportMonth}_${currentReportBranch}.pdf`,
        image: { type: "jpeg", quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { unit: "in", format: "letter", orientation: "portrait" },
    };

    html2pdf().set(opt).from(container).save();
}


// Add this function for motorcycle report PDF export
function generateMotorcycleReportPDF() {
    const reportContent = $('#monthlyReportContent').html();
    const brandFilter = $('#reportBrandFilter').val();
    const branch = $('#reportBranch').val() || currentUserBranch;
    
    exportMotorcycleReportToPDF(reportContent, branch, brandFilter);
}
// Add this function for motorcycle report PDF export
function generateMotorcycleReportPDF() {
    const reportContent = $('#monthlyReportContent').html();
    const brandFilter = $('#reportBrandFilter').val();
    const branch = $('#reportBranch').val() || currentUserBranch;
    
    exportMotorcycleReportToPDF(reportContent, branch, brandFilter);
}

function generateInventoryReportPDF() {
    const [year, monthNum] = currentReportMonth.split("-");
    const monthName = new Date(year, monthNum - 1, 1).toLocaleString("default", {
        month: "long",
    });
    const branchName = currentReportBranch === "all" ? "All Branches" : currentReportBranch;

    // Use same variable names as renderMonthlyInventoryReport
    const beginningBalance = currentReportSummary?.beginning_balance || 0;
    const receivedTransfers = currentReportSummary?.received_transfers || 0;
    const newDeliveries = currentReportSummary?.new_deliveries || 0;
    const totalIn = currentReportSummary?.in || 0;
    const transfersOut = currentReportSummary?.transfers_out || 0;
    const soldDuringMonth = currentReportSummary?.sold_during_month || 0;
    const totalOut = currentReportSummary?.out || 0;
    const endingCalculated = currentReportSummary?.ending_calculated || 0;
    const endingActual = currentReportSummary?.ending_actual || 0;
    
    // Inventory cost values
    const costBeginning = currentReportSummary?.inventory_cost?.beginning_balance || 0;
    const costReceived = currentReportSummary?.inventory_cost?.received_transfers || 0;
    const costNewDeliveries = currentReportSummary?.inventory_cost?.new_deliveries || 0;
    const costTotalIn = currentReportSummary?.inventory_cost?.in || 0;
    const costTransfersOut = currentReportSummary?.inventory_cost?.transfers_out || 0;
    const costSoldDuringMonth = currentReportSummary?.inventory_cost?.sold_during_month || 0;
    const costTotalOut = currentReportSummary?.inventory_cost?.out || 0;
    const costEndingCalculated = currentReportSummary?.inventory_cost?.ending_calculated || 0;
    const costEndingActual = currentReportSummary?.inventory_cost?.ending_actual || 0;

    // Sort data by model for cleaner display
    currentReportData.sort((a, b) => a.model.localeCompare(b.model));

    const rowsHtml = currentReportData.length === 0 ? `
        <tr>
            <td colspan="7" style="text-align: center; padding: 30px; color: #6c757d; font-style: italic;">
                No inventory data found for this period
            </td>
        </tr>
    ` : currentReportData.map((item, index) => {
        const rowBg = index % 2 === 0 ? "#ffffff" : "#f8f9fa";
        return `
            <tr style="background-color: ${rowBg};">
                <td style="text-align: center; padding: 8px; border-right: 1px solid #e9ecef;">1</td>
                <td style="padding: 8px; border-right: 1px solid #e9ecef;">${escapeHtml(item.model)}</td>
                <td style="padding: 8px; border-right: 1px solid #e9ecef;">${escapeHtml(item.color)}</td>
                <td style="padding: 8px; border-right: 1px solid #e9ecef;">${escapeHtml(item.brand)}</td>
                <td style="padding: 8px; border-right: 1px solid #e9ecef;">${escapeHtml(item.engine_number)}</td>
                <td style="padding: 8px; border-right: 1px solid #e9ecef;">${escapeHtml(item.frame_number)}</td>
                <td style="padding: 8px; text-align: right;">${formatCurrency(item.inventory_cost)}</td>
            </tr>
        `;
    }).join("");

    const html = `
        <div style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px;">
            <!-- Header Section -->
            <div style="text-align: center; margin-bottom: 30px;">
                <div style="display: flex; align-items: center; justify-content: center; margin-bottom: 15px;">
                    <div style="width: 40px; height: 2px; background: #000f71; margin-right: 15px;"></div>
                    <h4 style="margin: 0; color: #000f71; font-weight: 600; letter-spacing: 0.5px;">
                        SOLID MOTORCYCLE DISTRIBUTORS, INC.
                    </h4>
                    <div style="width: 40px; height: 2px; background: #000f71; margin-left: 15px;"></div>
                </div>
                <h5 style="margin: 10px 0; color: #495057; font-weight: 500;">MONTHLY INVENTORY BALANCE REPORT</h5>
                <h6 style="margin: 5px 0; color: #6c757d; font-weight: 400;">${monthName} ${year}</h6>
                ${currentReportBranch !== "all" ? `
                    <p style="margin: 5px 0;">
                        <span style="color: #6c757d;">Branch:</span> 
                        <span style="color: #000f71; font-weight: 500;">${branchName}</span>
                    </p>
                ` : ""}
                <p style="color: #6c757d; font-size: 12px; margin: 5px 0;">
                    Generated on ${new Date().toLocaleDateString("en-US", {
                        weekday: "long",
                        year: "numeric",
                        month: "long",
                        day: "numeric",
                    })}
                </p>
            </div>
            
            <!-- Table Section -->
            <div style="border: 1px solid #e9ecef; border-radius: 6px; margin-bottom: 30px;">
                <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
                    <thead>
                        <tr style="background-color: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                            <th style="text-align: center; padding: 12px; font-weight: 600; color: #495057; width: 60px;">QTY</th>
                            <th style="padding: 12px; font-weight: 600; color: #495057;">MODEL</th>
                            <th style="padding: 12px; font-weight: 600; color: #495057;">COLOR</th>
                            <th style="padding: 12px; font-weight: 600; color: #495057;">BRAND</th>
                            <th style="padding: 12px; font-weight: 600; color: #495057;">ENGINE NUMBER</th>
                            <th style="padding: 12px; font-weight: 600; color: #495057;">FRAME NUMBER</th>
                            <th style="padding: 12px; font-weight: 600; color: #495057;">Inventory Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rowsHtml}
                    </tbody>
                </table>
            </div>
            
            <!-- Summary Section - 4 Cards in a Row -->
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 15px; margin-bottom: 30px;">
                <!-- 1. Beginning Balance -->
                <div style="text-align: center; padding: 20px; background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px;">
                    <div style="font-weight: 600; color: #495057; margin-bottom: 8px; font-size: 14px;">
                        BEGINNING BALANCE
                    </div>
                    <div style="font-size: 28px; font-weight: bold; color: #6c757d; margin-bottom: 5px;">
                        ${beginningBalance}
                    </div>
                    <div style="font-size: 12px; color: #6c757d;">
                        ${formatCurrency(costBeginning)}
                    </div>
                </div>
                
                <!-- 2. IN -->
                <div style="text-align: center; padding: 20px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 8px;">
                    <div style="font-weight: 600; color: #155724; margin-bottom: 8px; font-size: 14px;">
                        IN
                    </div>
                    <div style="font-size: 28px; font-weight: bold; color: #28a745; margin-bottom: 5px;">
                        ${totalIn}
                    </div>
                    <div style="font-size: 12px; color: #28a745; margin-bottom: 8px;">
                        ${formatCurrency(costTotalIn)}
                    </div>
                    <div style="font-size: 10px; color: #155724;">
                        Received: ${receivedTransfers} | New: ${newDeliveries}
                    </div>
                </div>
                
                <!-- 3. OUT -->
                <div style="text-align: center; padding: 20px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 8px;">
                    <div style="font-weight: 600; color: #721c24; margin-bottom: 8px; font-size: 14px;">
                        OUT
                    </div>
                    <div style="font-size: 28px; font-weight: bold; color: #dc3545; margin-bottom: 5px;">
                        ${totalOut}
                    </div>
                    <div style="font-size: 12px; color: #dc3545; margin-bottom: 8px;">
                        ${formatCurrency(costTotalOut)}
                    </div>
                    <div style="font-size: 10px; color: #721c24;">
                        Transferred: ${transfersOut} | Sold: ${soldDuringMonth}
                    </div>
                </div>
                
                <!-- 4. Ending Balance -->
                <div style="text-align: center; padding: 20px; background: #cce5ff; border: 1px solid #b3d7ff; border-radius: 8px;">
                    <div style="font-weight: 600; color: #004085; margin-bottom: 8px; font-size: 14px;">
                        ENDING BALANCE
                    </div>
                    <div style="font-size: 28px; font-weight: bold; color: #0056b3; margin-bottom: 5px;">
                        ${endingActual}
                    </div>
                    <div style="font-size: 12px; color: #0056b3; margin-bottom: 8px;">
                        ${formatCurrency(costEndingActual)}
                    </div>
                    <div style="font-size: 10px; color: #004085;">
                        Calculated: ${endingCalculated}
                        ${endingCalculated !== endingActual ? ` | Diff: ${endingActual - endingCalculated}` : ''}
                    </div>
                </div>
            </div>
            
            
            </div>
        </div>
    `;

    const container = document.createElement("div");
    container.innerHTML = html;

    const opt = {
        margin: 0.5,
        filename: `Monthly_Inventory_Report_${currentReportMonth}_${currentReportBranch}.pdf`,
        image: { type: "jpeg", quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { unit: "in", format: "letter", orientation: "portrait" },
    };

    html2pdf().set(opt).from(container).save();
}


function generateTransferredSummary(month, branch) {
    $("#monthlyReportContent").html(
        '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>'
    );

    $.ajax({
        url: "../api/inventory_management.php",
        method: "GET",
        data: {
            action: "get_monthly_transferred_summary",
            month: month,
            branch: branch,
        },
        dataType: "json",
        success: function (response) {
            if (response.success) {
                currentReportData = response.data;
                currentReportMonth = response.month;
                currentReportBranch = response.branch;
                currentReportType = 'transferred'; // Set report type
                
                renderTransferredSummaryReport(response.data, response.month, response.branch, response.summary);
                $("#monthlyInventoryReportModal").modal("show");
            } else {
                showErrorModal(response.message || "Error generating transferred summary");
            }
        },
        error: function (xhr, status, error) {
            showErrorModal("Error generating transferred summary: " + error);
        }
    });
}


function renderTransferredSummaryReport(data, month, branch, summary) {
    const [year, monthNum] = month.split("-");
    const monthName = new Date(year, monthNum - 1, 1).toLocaleString("default", {
        month: "long",
    });

    // Use the summary data passed to the function, not from response.data
    const totalTransferred = summary?.total_transferred || 0;
    const totalInventoryCost = summary?.total_inventory_cost || 0;

    let html = `
        <div class="report-header text-center mb-4">
            <div class="d-flex align-items-center justify-content-center mb-2">
                <div style="width: 40px; height: 2px; background: #000f71; margin-right: 15px;"></div>
                <h4 class="mb-0" style="color: #000f71; font-weight: 600; letter-spacing: 0.5px;">
                    SOLID MOTORCYCLE DISTRIBUTORS, INC.
                </h4>
                <div style="width: 40px; height: 2px; background: #000f71; margin-left: 15px;"></div>
            </div>
            <h5 class="mb-2" style="color: #495057; font-weight: 500;">MONTHLY SUMMARY OF TRANSFERRED STOCKS</h5>
            <h6 class="mb-2 text-muted" style="font-weight: 400;">${monthName} ${year}</h6>
            <p class="mb-1"><span style="color: #6c757d;">Branch:</span> 
             <span style="color: #000f71; font-weight: 500;">${branch}</span></p>
            <p class="text-muted small mb-0" style="font-size: 0.85rem;">
                Generated on ${new Date().toLocaleDateString("en-US", {
                    weekday: "long",
                    year: "numeric",
                    month: "long",
                    day: "numeric",
                })}
            </p>
        </div>
        
        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm text-center" style="background: linear-gradient(135deg, #000f71, #1a237e); color: white;">
                    <div class="card-body py-4">
                        <h6 class="card-title mb-2 text-white">TOTAL TRANSFERRED</h6>
                        <h3 class="mb-0 text-white">${totalTransferred}</h3>
                        <small>Motorcycles transferred</small>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm text-center" style="background: linear-gradient(135deg, #28a745, #20c997); color: white;">
                    <div class="card-body py-4">
                        <h6 class="card-title mb-2 text-white">TOTAL INVENTORY COST</h6>
                        <h3 class="mb-0 text-white">${formatCurrency(totalInventoryCost)}</h3>
                        <small>Total value transferred</small>
                    </div>
                </div>
            </div>
        </div>
    `;

    if (data.length === 0) {
        html += `
            <div class="alert alert-info text-center">
                <i class="bi bi-info-circle me-2"></i>
                No transfers found for ${monthName} ${year} from ${branch} branch.
            </div>
        `;
    } else {
        html += `
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Invoice Number</th>
                            <th>Model</th>
                            <th>Brand</th>
                            <th>Color</th>
                            <th>Engine Number</th>
                            <th>Frame Number</th>
                            <th>Transfer Date</th>
                            <th>Transferred To</th>
                            <th class="text-end">Inventory Cost</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        data.forEach((item, index) => {
            // Get brand color
            let brandColor = '';
            switch (item.brand.toLowerCase()) {
                case 'honda':
                    brandColor = 'text-dark'; // Red
                    break;
                case 'yamaha':
                    brandColor = 'text-dark'; // Black
                    break;
                case 'suzuki':
                    brandColor = 'text-dark'; // Blue
                    break;
                case 'kawasaki':
                    brandColor = 'text-dark'; // Green
                    break;
                case 'asiastar':
                    brandColor = 'text-dark'; // Yellow
                    break;
                default:
                    brandColor = 'text-dark';
            }

            html += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${escapeHtml(item.invoice_number || 'N/A')}</td>
                    <td>${escapeHtml(item.model)}</td>
                    <td class="${brandColor} fw-bold">${escapeHtml(item.brand)}</td>
                    <td>${escapeHtml(item.color)}</td>
                    <td><code>${escapeHtml(item.engine_number)}</code></td>
                    <td><code>${escapeHtml(item.frame_number)}</code></td>
                    <td>${formatDate(item.transfer_date)}</td>
                    <td><span class="badge bg-info">${escapeHtml(item.transferred_to)}</span></td>
                    <td class="text-end fw-bold">${formatCurrency(item.inventory_cost)}</td>
                </tr>
            `;
        });

        html += `
                    </tbody>
                    <tfoot class="table-group-divider">
                        <tr class="table-active">
                            <td colspan="9" class="text-end fw-bold">Total:</td>
                            <td class="text-end fw-bold">${formatCurrency(totalInventoryCost)}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        `;
    }

    $("#monthlyReportContent").html(html);
}
// =======================
// Helper Functions
// =======================
function formatDate(dateString) {
  if (!dateString) return "N/A";
  const date = new Date(dateString);
  return date.toLocaleDateString("en-PH", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
}

function formatCurrency(amount) {
  if (amount === null || amount === undefined) return "N/A";
  if (isNaN(amount)) return "N/A";
  return (
    "₱" +
    parseFloat(amount).toLocaleString("en-PH", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    })
  );
}


function capitalizeFirstLetter(string) {
  return string.charAt(0).toUpperCase() + string.slice(1);
}

function escapeHtml(text) {
  if (text === null || text === undefined) return "";
  const stringText = String(text);
  return stringText
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

function groupByModel(items) {
  return items.reduce((groups, item) => {
    const key = `${item.brand} ${item.model}`;
    if (!groups[key]) groups[key] = [];
    groups[key].push(item);
    return groups;
  }, {});
}

function getStatusClass(status) {
  switch (status) {
    case "available":
      return "bg-success";
    case "sold":
      return "bg-danger";
    case "transferred":
      return "bg-warning text-dark";
    default:
      return "bg-secondary";
  }
}
