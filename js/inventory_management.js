// =======================
// Global Variables
// =======================
let currentInventoryPage = 1;
let totalInventoryPages = 1;
let currentInventorySort = "";
let currentInventoryQuery = "";
let selectedRecordIds = [];
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
  loadBranchInventory(currentBranch);
  setInterval(checkIncomingTransfers, 1000);
  addModelForm();
  setTimeout(() => {
    if ($("#branchMap").length) {
      map = initMap(currentBranch);
    }
  }, 300);
});

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

  // Incoming Transfers
  $("#acceptAllTransfersBtn").click(function () {
    const transferIds = [];
    $("#incomingTransfersBody tr").each(function () {
      const id = $(this).data("transfer-id");
      if (id) transferIds.push(id);
    });
    if (transferIds.length === 0) {
      showErrorModal("No transfers to accept");
      return;
    }
    $.ajax({
      url: "../api/inventory_management.php",
      method: "POST",
      data: {
        action: "accept_transfers",
        transfer_ids: transferIds.join(","),
        current_branch: currentBranch,
      },
      dataType: "json",
      success: function (response) {
        if (response.success) {
          showSuccessModal(response.message || "Transfers accepted successfully! Received on: " + response.date_received);
          $("#incomingTransfersModal").modal("hide");
          setTimeout(function () {
            window.location.reload();
          }, 2000);
          hasShownIncomingTransfers = false;
        } else {
          showErrorModal(response.message || "Error accepting transfers");
        }
      },
      error: function (xhr, status, error) {
        showErrorModal("Error accepting transfers: " + error);
      },
    });
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
  modal.off("click", "#confirmActionBtn");
  modal.on("click", "#confirmActionBtn", function () {
    modal.modal("hide");
    if (typeof callback === "function") callback();
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
    html =
      '<div class="col-12 text-center py-5 text-muted">No inventory data found</div>';
  } else {
    const brands = {};
    data.forEach((item) => {
      if (!brands[item.brand]) {
        brands[item.brand] = [];
      }
      brands[item.brand].push(item);
    });

    for (const brand in brands) {
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
        default:
          brandColor = "border-secondary bg-secondary-light";
      }

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
    }
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
      '<tr><td colspan="11" class="text-center py-5 text-muted">No inventory data found</td></tr>';
  } else {
    data.forEach((item) => {
      html += `
                <tr data-id="${item.id}">
                <td>${item.invoice_number || "N/A"}</td>
                    <td>${formatDate(item.date_delivered)}</td>
                    <td>${item.brand}</td>
                    <td>${item.model}</td>
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
      color: $(this).find(".model-color").val(),
      inventory_cost: $(this).find(".model-inventoryCost").val(),
      quantity: $(this).find(".model-quantity").val(),
      details: [],
    };

    if (
      !modelData.brand ||
      !modelData.model ||
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
      if (response.success) {
        $("#addMotorcycleModal").modal("hide");
        $(".modal-backdrop").remove();
        $("body").removeClass("modal-open");

        $("#addMotorcycleForm")[0].reset();
        $("#modelFormsContainer").empty();
        modelCount = 0;

        showSuccessModal("Motorcycles added successfully!");

        loadInventoryDashboard();
        loadInventoryTable(
          currentInventoryPage,
          currentInventorySort,
          currentInventoryQuery
        );
      } else {
        if (response.message === "DUPLICATE_INVOICE") {
          showInvoiceError("An invoice with this number already exists");
          $("#invoiceNumber").focus();
        } else {
          showErrorModal(response.message || "Error adding motorcycles");
        }
      }
    },
    error: function (xhr, status, error) {
      showErrorModal("Error adding motorcycles: " + error);
    },
  });
}
function updateMotorcycle() {
  const formData = {
    action: "update_motorcycle",
    id: $("#editId").val(),
    date_delivered: $("#editDateDelivered").val(),
    brand: $("#editBrand").val(),
    model: $("#editModel").val(),
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
      if (response.success) {
        $("#editMotorcycleModal").modal("hide");
        showSuccessModal("Motorcycle updated successfully!");
        loadInventoryTable(
          currentInventoryPage,
          currentInventorySort,
          currentInventoryQuery
        );
      } else {
        showErrorModal(response.message || "Error updating motorcycle");
      }
    },
    error: function (xhr, status, error) {
      showErrorModal("Error updating motorcycle: " + error);
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
$("#invoiceNumber").on("blur", function () {
  checkInvoiceNumber($(this).val());
});
$("#addMotorcycleForm").on("submit", function (e) {
  const invoiceNumber = $("#invoiceNumber").val();
  if (invoiceNumber) {
    e.preventDefault();
    checkInvoiceNumber(invoiceNumber, true);
  }
});
function checkInvoiceNumber(invoiceNumber, isSubmit = false) {
  if (!invoiceNumber) return;

  $.ajax({
    url: "../api/inventory_management.php",
    method: "POST",
    data: {
      action: "check_invoice_number",
      invoice_number: invoiceNumber,
    },
    dataType: "json",
    success: function (response) {
      if (response.exists) {
        showInvoiceError("An invoice with this number already exists");
        if (isSubmit) {
          $("#invoiceNumber").focus();
        }
      } else {
        clearInvoiceError();
        if (isSubmit) {
          $("#addMotorcycleForm").off("submit").submit();
        }
      }
    },
    error: function () {
      if (isSubmit) {
        $("#addMotorcycleForm").off("submit").submit();
      }
    },
  });
}

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
    "ROXAS SUZUKI",
    "MAMBUSAO",
    "SIGMA",
    "PRC",
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
    "BAILAN",
    "3SMB",
    "3SMINDORO",
    "MANSALAY",
    "K-RIDERS",
    "IBAJAY",
    "NUMANCIA",
    "CEBU",
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

  if (selectedIds.length === 0) {
    showErrorModal("Please select at least one motorcycle to transfer");
    return;
  }

 const formData = {
    action: "transfer_multiple_motorcycles",
    motorcycle_ids: selectedIds.join(","),
    inventory_costs: inventoryCosts.join(","), // Send inventory costs
    from_branch: $("#multipleFromBranch").val(),
    to_branch: $("#multipleToBranch").val(),
    transfer_date: $("#multipleTransferDate").val(),
    notes: $("#multipleTransferNotes").val(),
  };

  if (
    !formData.motorcycle_ids ||
    !formData.from_branch ||
    !formData.to_branch ||
    !formData.transfer_date
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
    // In the performMultipleTransfers function, update the AJAX success handler:
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

function showTransferReceipt(receiptData) {
    if (!receiptData) return;
    
    // Set header information
    $("#receiptDate").text(new Date().toLocaleDateString());
    $("#receiptTransferId").text(receiptData.transfer_ids.join(', '));
    $("#receiptFromBranch").text(receiptData.from_branch);
    $("#receiptToBranch").text(receiptData.to_branch);
    
    // Set notes or show default message
    if (receiptData.notes && receiptData.notes.trim() !== '') {
        $("#receiptNotes").text(receiptData.notes);
    } else {
        $("#receiptNotes").text('No notes provided.');
    }
    
    // Populate motorcycles list
    const $receiptList = $("#receiptMotorcyclesList");
    $receiptList.empty();
    
    let totalCost = 0;
    
    receiptData.motorcycles.forEach((motorcycle, index) => {
        const cost = parseFloat(motorcycle.inventory_cost) || 0;
        totalCost += cost;
        
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
    
    // Set totals
    $("#receiptTotalCount").text(receiptData.total_count);
    $("#receiptTotalCost").text(formatCurrency(totalCost));
    
    // Show the modal
    $("#transferReceiptModal").modal("show");
    
    // Add print functionality
    $("#printReceiptBtn").off("click").on("click", function() {
        printReceipt();
    });
}

function printReceipt() {
    const receiptContent = document.getElementById("transferReceiptModal").querySelector(".modal-content");
    const originalDisplay = receiptContent.style.display;
    
    // Show the content for printing
    receiptContent.style.display = "block";
    
    const opt = {
        margin: 10,
        filename: 'transfer_receipt.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };
    
    html2pdf().set(opt).from(receiptContent).save().then(() => {
        // Restore original display
        receiptContent.style.display = originalDisplay;
    });
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
  updateTransferSummary();
  searchMotorcyclesByEngine();
}

function updateTransferSummary() {
  const $selectedCount = $("#selectedCount");
  const $totalInventoryCostValue = $("#totalInventoryCostValue");
  const $selectionProgress = $("#selectionProgress");

  $selectedCount.text(selectedMotorcycles.length);

  const totalInventoryCost = selectedMotorcycles.reduce(
    (sum, motorcycle) => sum + (parseFloat(motorcycle.inventory_cost) || 0),
    0
  );
  $totalInventoryCostValue.text(formatCurrency(totalInventoryCost));

  const progressPercentage = Math.min(
    (selectedMotorcycles.length / 10) * 100,
    100
  );
  $selectionProgress.css("width", progressPercentage + "%");
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
                        <span class="input-group-text"></span>
                        <input type="number" step="0.01" class="form-control" 
                               id="inventory-cost-${motorcycle.id}" 
                               value="${motorcycle.inventory_cost || ''}" 
                               placeholder="Enter inventory cost">
                        <button class="btn btn-outline-success" type="button" onclick="saveCost(${motorcycle.id})">
                            <i class="bi bi-check-lg"></i> Save
                        </button>
                    </div>
                </div>
            </div>
        `;
  });

  $selectedList.html(html);
}

// Function to save cost for a specific motorcycle
function saveCost(motorcycleId) {
  const costInput = document.getElementById(`inventory-cost-${motorcycleId}`);
  const newCost = parseFloat(costInput.value);
  
  if (!isNaN(newCost) && newCost > 0) {
    // Update the motorcycle object
    const motorcycle = selectedMotorcycles.find(m => m.id === motorcycleId);
    if (motorcycle) {
      motorcycle.inventory_cost = newCost;
      showSuccessModal("Cost updated successfully!");
      updateTransferSummary();
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

  if (transfers.length === 0) {
    tbody.append(`
            <tr>
                <td colspan="6" class="text-center py-4 text-muted">No incoming transfers found</td>
            </tr>
        `);
  } else {
    transfers.forEach((transfer) => {
      tbody.append(`
                <tr data-transfer-id="${transfer.transfer_id}">
                    <td>${transfer.brand} ${transfer.model}</td>
                    <td>${transfer.engine_number}</td>
                    <td>${transfer.frame_number}</td>
                    <td>${transfer.color}</td>
                    <td>${formatDate(transfer.transfer_date)}</td>
                    <td>${transfer.from_branch}</td>
                </tr>
            `);
    });
  }

  if (!hasShownIncomingTransfers) {
    $("#incomingTransfersModal").modal("show");
    hasShownIncomingTransfers = true;
  }
}
// =======================
// Branch Inventory & Map
// =======================
function initMap(currentBranch) {
  const map = L.map("branchMap").setView([11.5852, 122.7511], 10);

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "&copy; OpenStreetMap contributors",
  }).addTo(map);

  $.get(
    "../api/inventory_management.php",
    {
      action: "get_branches_with_inventory",
    },
    function (response) {
      if (response.success) {
        const branchCoordinates = {
          "ROXAS-SUZUKI": { lat: 11.581639063474135, lng: 122.75283046163139 },
          "ROXAS-HONDA": { lat: 11.591933174094493, lng: 122.75177370058198 },
          MAMBUSAO: { lat: 11.430722236714315, lng: 122.60106183558217 },
          "ANTIQUE-1": { lat: 10.747081312946916, lng: 121.94138590805788 },
          "ANTIQUE-2": { lat: 10.749653220828158, lng: 121.94142882340054 },
          "DELGADO HONDA": { lat: 10.697818450677735, lng: 122.56464019830032 },
          "DELGADO SUZUKI": {
            lat: 10.697818450677735,
            lng: 122.56464019830032,
          },
          "JARO-1": { lat: 10.746529482552543, lng: 122.56703172463938 },
          "JARO-2": { lat: 10.749878260560397, lng: 122.56812797163823 },
          "KALIBO MABINI": { lat: 11.726705198816557, lng: 122.36889838061255 },
          "KALIBO SUZUKI": { lat: 11.702856917692344, lng: 122.36675785507218 },
          ALTAVAS: { lat: 11.581991439599044, lng: 122.75273929376398 },
          EMAP: { lat: 11.581991439599044, lng: 122.75273929376398 },
          CULASI: { lat: 11.428798698065513, lng: 122.05695055376913 },
          BACOLOD: { lat: 10.670965032727254, lng: 122.95977720190973 },
          "PASSI-1": { lat: 11.105396570048141, lng: 122.64601950262048 },
          "PASSI-2": { lat: 11.106284551766606, lng: 122.64677038445016 },
          BALASAN: { lat: 11.46865937405874, lng: 123.09560889637078 },
          GUIMARAS: { lat: 10.605846163901681, lng: 122.58799192677242 },
          PEMDI: { lat: 10.65556975930108, lng: 122.93918296725195 },
          EEMSI: { lat: 10.605758954854227, lng: 122.58813091469503 },
          AJUY: { lat: 11.179194176167435, lng: 123.01975649183555 },
          BAILAN: { lat: 11.450895697343983, lng: 122.82968507428964 },
          "3SMB": { lat: 12.602606955880981, lng: 121.5037542414926 },
          "3SMINDORO": { lat: 12.371133617009118, lng: 121.06330210820141 },
          MANSALAY: { lat: 12.530846939769289, lng: 121.44707141396867 },
          "K-RIDERS": { lat: 11.626344148372608, lng: 122.73960109140822 },
          IBAJAY: { lat: 11.815513408059678, lng: 122.15988390959608 },
          NUMANCIA: { lat: 11.716374415728836, lng: 122.35946468260876 },
          HEADOFFICE: { lat: 11.58156063320175, lng: 122.75277786727027 },
          CEBU: { lat: 10.315699, lng: 123.885437 },
        };

        response.data.forEach((branch) => {
          if (branch.total_quantity > 0) {
            const coord = branchCoordinates[branch.branch] || {
              lat: 11.5852,
              lng: 122.7511,
            };
            const isCurrent = branch.branch === currentBranch;

            const marker = L.marker([coord.lat, coord.lng], {
              icon: L.divIcon({
                className: `branch-marker ${isCurrent ? "current-branch" : ""}`,
                html: branch.branch.substring(0, 2),
                iconSize: [30, 30],
              }),
            }).addTo(map);

            marker.bindPopup(`
                        <b>Branch ${branch.branch}</b><br>
                        <small>${branch.total_quantity} units available</small>
                    `);

            marker.on("click", function () {
              loadBranchInventory(branch.branch);
            });
          }
        });
      }
    },
    "json"
  );

  return map;
}

function loadBranchInventory(branchCode) {
  $("#branchInfo").html(
    `<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>`
  );
  $("#modelList").empty();

  $.get(
    "../api/inventory_management.php",
    {
      action: "get_branch_inventory",
      branch: branchCode,
      status: "all",
    },
    function (response) {
      if (response.success && response.data.length > 0) {
        $("#branchInfo").html(`
                <h6>Branch: <strong>${branchCode}</strong></h6>
                <p class="small">${response.data.length} units available</p>
            `);

        const modelGroups = groupByModel(response.data);
        let html = "";

        Object.keys(modelGroups).forEach((model) => {
          const items = modelGroups[model];
          html += `
                    <div class="card mb-2 model-item" data-model="${model}">
                        <div class="card-body">
                            <h6 class="card-title">${model}</h6>
                            <p class="card-text small">
                                ${items.length} available · 
                                ${items[0].color} · 
                                ${items[0].current_branch}
                            </p>
                        </div>
                    </div>
                `;
        });

        $("#modelList").html(html);

        $(".model-item").click(function () {
          const model = $(this).data("model");
          viewModelDetails(modelGroups[model][0].id);
        });
      } else {
        $("#branchInfo").html(`
                <h6>Branch: <strong>${branchCode}</strong></h6>
                <p class="text-muted">No inventory available</p>
            `);
        $("#modelList").html('<p class="text-muted">No models found</p>');
      }
    },
    "json"
  );
}
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
      if (response.success && response.data.length > 0) {
        const modelGroups = groupByModel(response.data);
        let html = "<h6>Search Results</h6>";

        Object.keys(modelGroups).forEach((model) => {
          const items = modelGroups[model];
          html += `
                    <div class="card mb-2 model-item" data-model="${model}" data-id="${items[0].id}">
                        <div class="card-body">
                            <h6 class="card-title">${model}</h6>
                            <p class="card-text small">
                                ${items.length} available · 
                                ${items[0].color} · 
                                ${items[0].current_branch}
                            </p>
                        </div>
                    </div>
                `;
        });

        $("#modelList").html(html);

        $(".model-item").click(function () {
          const id = $(this).data("id");
          viewMotorcycleDetails(id);
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

function viewMotorcycleDetails(id) {
  $("#detailsModal .modal-body").html(
    '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>'
  );

  $.get(
    "../api/inventory_management.php",
    {
      action: "get_motorcycle",
      id: id,
    },
    function (response) {
      if (response.success) {
        const data = response.data;

        $("#detailsModal .modal-body").html(`
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-black">Basic Information</h6>
                        <hr>
                        <p><strong>Invoice Number/MT:</strong> ${
                          data.invoice_number || "N/A"
                        }</p>
                        <p><strong>Brand:</strong> ${data.brand}</p>
                        <p><strong>Model:</strong> ${data.model}</p>
                        <p><strong>Color:</strong> ${data.color}</p>
                        <p><strong>Current Branch:</strong> ${
                          data.current_branch
                        }</p>
                        <p><strong>Status:</strong> <span class="badge ${getStatusClass(
                          data.status
                        )}">
                            ${
                              data.status.charAt(0).toUpperCase() +
                              data.status.slice(1)
                            }
                        </span></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-black">Identification & Pricing</h6>
                        <hr>
                        <p><strong>Engine #:</strong> ${data.engine_number}</p>
                        <p><strong>Frame #:</strong> ${data.frame_number}</p>
                        <p><strong>Date Delivered:</strong> ${formatDate(
                          data.date_delivered
                        )}</p>
                        <p><strong>Inventory Cost:</strong> ${
                          data.inventory_cost ? formatCurrency(data.inventory_cost) : "N/A"
                        }</p>
                    </div>
                </div>
            `);

        $("#detailsModal").modal("show");
      } else {
        $("#detailsModal .modal-body").html(
          '<div class="alert alert-danger">Error loading motorcycle details</div>'
        );
        $("#detailsModal").modal("show");
      }
    },
    "json"
  ).fail(function () {
    $("#detailsModal .modal-body").html(
      '<div class="alert alert-danger">Error loading motorcycle details</div>'
    );
    $("#detailsModal").modal("show");
  });
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
    "ROXAS SUZUKI",
    "MAMBUSAO",
    "SIGMA",
    "PRC",
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
    "BAILAN",
    "3SMB",
    "3SMINDORO",
    "MANSALAY",
    "K-RIDERS",
    "IBAJAY",
    "NUMANCIA",
    "CEBU",
  ];

  const $dropdown = $("#reportBranch");
  branches.forEach((branch) => {
    $dropdown.append(`<option value="${branch}">${branch}</option>`);
  });
}

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
                currentReportType = 'inventory'; // Set report type
                
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

  // ✅ Use summary values from backend, not recalculated here
  const totalIn = summary?.in || 0;
  const totalOut = summary?.out || 0;
  const endingBalance = summary?.ending || 0;
  const totalLcpEnding = summary?.inventory_cost?.ending || 0;

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
          <div class="card border-0 shadow-sm mb-4" style="border-radius: 8px;">
            <div class="card-header bg-transparent border-0 pt-4 pb-3">
              <h6 class="card-title text-center mb-0" style="color: #000f71; font-weight: 600; letter-spacing: 0.5px;">
                INVENTORY SUMMARY
              </h6>
            </div>
            <div class="card-body px-4 pb-4 pt-0">
              <div class="summary-item d-flex justify-content-between align-items-center mb-3 pb-3" style="border-bottom: 1px solid #f1f3f4;">
                <div>
                  <div class="fw-semibold" style="color: #495057;">IN</div>
                  <small class="text-muted" style="font-size: 0.8rem;">Inventory added during period</small>
                </div>
                <span class="fs-5 fw-bold" style="color: #28a745;">${totalIn}</span>
              </div>
              
              <div class="summary-item d-flex justify-content-between align-items-center mb-3 pb-3" style="border-bottom: 1px solid #f1f3f4;">
                <div>
                  <div class="fw-semibold" style="color: #495057;">OUT</div>
                  <small class="text-muted" style="font-size: 0.8rem;">Inventory transferred out</small>
                </div>
                <span class="fs-5 fw-bold" style="color: #dc3545;">${totalOut}</span>
              </div>
              
              <div class="summary-item d-flex justify-content-between align-items-center pt-2">
                <div>
                  <div class="fw-bold" style="color: #000f71;">ENDING BALANCE</div>
                  <small class="text-muted" style="font-size: 0.8rem;">Remaining inventory</small>
                </div>
                <span class="fs-4 fw-bold" style="color: #000f71;">${endingBalance}</span>
              </div>
            </div>
          </div>
          
          <!-- Inventory Cost Value Summary -->
          <div class="card border-0 shadow-sm" style="border-radius: 8px;">
            <div class="card-body px-4 pb-4 pt-0">
              <div class="summary-item d-flex justify-content-between align-items-center">
                <div class="fw-semibold text-secondary">Ending Inventory Cost Value</div>
                <span class="fs-6 fw-bold text-info">${formatCurrency(totalLcpEnding)}</span>
              </div>
            </div>
          </div>
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
        // Admin/HeadOffice can select any branch
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
        'HEADOFFICE', 'ROXAS SUZUKI', 'MAMBUSAO', 'SIGMA', 'PRC', 'CUARTERO', 'JAMINDAN',
        'ROXAS HONDA', 'ANTIQUE-1', 'ANTIQUE-2', 'DELGADO HONDA', 'DELGADO SUZUKI',
        'JARO-1', 'JARO-2', 'KALIBO MABINI', 'KALIBO SUZUKI', 'ALTAVAS', 'EMAP', 'CULASI',
        'BACOLOD', 'PASSI-1', 'PASSI-2', 'BALASAN', 'GUIMARAS', 'PEMDI', 'EEMSI', 'AJUY',
        'BAILAN', '3SMB', '3SMINDORO', 'MANSALAY', 'K-RIDERS', 'IBAJAY', 'NUMANCIA', 'CEBU'
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

    // Validate month selection
    if (!month) {
        showErrorModal("Please select a month.");
        return;
    }

    // For non-admin users, use their branch
    const reportBranch = branch || currentUserBranch;

    $('#monthlyReportOptionsModal').modal('hide');
    $('#monthlyReportContent').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>');

    if (reportType === 'inventory') {
        generateMonthlyInventoryReport(month, reportBranch);
    } else if (reportType === 'transferred') {
        generateTransferredSummary(month, reportBranch);
    }
}
function generateReportPDF() {
    if (!currentReportData || !currentReportMonth || !currentReportType) {
        showErrorModal("Please generate a report first before exporting to PDF");
        return;
    }

    if (currentReportType === 'inventory') {
        generateInventoryReportPDF();
    } else if (currentReportType === 'transferred') {
        generateTransferredReportPDF();
    }
}

function generateTransferredReportPDF() {
    if (!currentReportData || !currentReportMonth || currentReportType !== 'transferred') {
        showErrorModal("Please generate a transferred summary report first before exporting to PDF");
        return;
    }
    
    const [year, monthNum] = currentReportMonth.split("-");
    const monthName = new Date(year, monthNum - 1, 1).toLocaleString("default", {
        month: "long",
    });

    // ✅ Compute totals (use summary if available, else calculate)
    const totalTransferred = currentReportSummary?.total_transferred || currentReportData.length;
    const totalInventoryCost = currentReportSummary?.total_inventory_cost || currentReportData.reduce((sum, item) => {
        return sum + (parseFloat(item.inventory_cost) || 0);
    }, 0);

    let html = `
        <div style="font-family: Arial, sans-serif; padding: 20px;">
            <div style="text-align: center; margin-bottom: 30px;">
                <div style="display: flex; align-items: center; justify-content: center; margin-bottom: 15px;">
                    <div style="width: 40px; height: 2px; background: #000f71; margin-right: 15px;"></div>
                    <h4 style="margin: 0; color: #000f71; font-weight: 600; letter-spacing: 0.5px;">SOLID MOTORCYCLE DISTRIBUTORS, INC.</h4>
                    <div style="width: 40px; height: 2px; background: #000f71; margin-left: 15px;"></div>
                </div>
                <h5 style="margin: 10px 0; color: #495057; font-weight: 500;">MONTHLY SUMMARY OF TRANSFERRED STOCKS</h5>
                <h6 style="margin: 5px 0; color: #6c757d; font-weight: 400;">${monthName} ${year}</h6>
                <p style="margin: 5px 0;"><span style="color: #6c757d;">Branch:</span> <span style="color: #000f71; font-weight: 500;">${currentReportBranch}</span></p>
                <p style="color: #6c757d; font-size: 12px; margin: 5px 0;">Generated on ${new Date().toLocaleDateString("en-US", {
                    weekday: "long",
                    year: "numeric",
                    month: "long",
                    day: "numeric",
                })}</p>
            </div>
            
            <!-- Summary Section -->
            <div style="display: flex; justify-content: space-around; margin-bottom: 30px;">
                <div style="text-align: center; padding: 20px; background: linear-gradient(135deg, #000f71, #1a237e); color: white; border-radius: 10px; width: 45%;">
                    <div style="font-weight: 600; margin-bottom: 10px; font-size: 16px;">TOTAL TRANSFERRED</div>
                    <div style="font-size: 28px; font-weight: bold;">${totalTransferred}</div>
                    <div style="font-size: 12px; opacity: 0.9;">Motorcycles transferred</div>
                </div>
                
                <div style="text-align: center; padding: 20px; background: linear-gradient(135deg, #28a745, #20c997); color: white; border-radius: 10px; width: 45%;">
                    <div style="font-weight: 600; margin-bottom: 10px; font-size: 16px;">TOTAL INVENTORY COST</div>
                    <div style="font-size: 28px; font-weight: bold;">${formatCurrency(totalInventoryCost)}</div>
                    <div style="font-size: 12px; opacity: 0.9;">Total value transferred</div>
                </div>
            </div>
    `;

    if (currentReportData.length === 0) {
        html += `
            <div style="text-align: center; padding: 40px; color: #6c757d; font-style: italic;">
                No transfers found for ${monthName} ${year} from ${currentReportBranch} branch.
            </div>
        `;
    } else {
        html += `
            <div style="border: 1px solid #e9ecef; border-radius: 6px; margin-bottom: 20px;">
                <table style="width: 100%; border-collapse: collapse; font-size: 10px;">
                    <thead>
                        <tr style="background-color: #343a40; color: white;">
                            <th style="padding: 10px; font-weight: 600; text-align: center; width: 30px;">#</th>
                            <th style="padding: 10px; font-weight: 600; text-align: left;">Invoice Number</th>
                            <th style="padding: 10px; font-weight: 600; text-align: left;">Model</th>
                            <th style="padding: 10px; font-weight: 600; text-align: left;">Brand</th>
                            <th style="padding: 10px; font-weight: 600; text-align: left;">Color</th>
                            <th style="padding: 10px; font-weight: 600; text-align: left;">Engine Number</th>
                            <th style="padding: 10px; font-weight: 600; text-align: left;">Frame Number</th>
                            <th style="padding: 10px; font-weight: 600; text-align: left;">Transfer Date</th>
                            <th style="padding: 10px; font-weight: 600; text-align: left;">Transferred To</th>
                            <th style="padding: 10px; font-weight: 600; text-align: right;">Inventory Cost</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        currentReportData.forEach((item, index) => {
            // Get brand color for PDF
            let brandColor = '#000000'; // Default black
            switch (item.brand.toLowerCase()) {
                case 'honda':
                    brandColor = '#dc3545'; // Red
                    break;
                case 'yamaha':
                    brandColor = '#000000'; // Black
                    break;
                case 'suzuki':
                    brandColor = '#007bff'; // Blue
                    break;
                case 'kawasaki':
                    brandColor = '#28a745'; // Green
                    break;
            }

            html += `
                <tr style="${index % 2 === 0 ? 'background-color: #f8f9fa;' : ''}">
                    <td style="padding: 8px; border-bottom: 1px solid #e9ecef; text-align: center;">${index + 1}</td>
                    <td style="padding: 8px; border-bottom: 1px solid #e9ecef;">${escapeHtml(item.invoice_number || 'N/A')}</td>
                    <td style="padding: 8px; border-bottom: 1px solid #e9ecef;">${escapeHtml(item.model)}</td>
                    <td style="padding: 8px; border-bottom: 1px solid #e9ecef; color: ${brandColor}; font-weight: bold;">${escapeHtml(item.brand)}</td>
                    <td style="padding: 8px; border-bottom: 1px solid #e9ecef;">${escapeHtml(item.color)}</td>
                    <td style="padding: 8px; border-bottom: 1px solid #e9ecef; font-family: monospace;">${escapeHtml(item.engine_number)}</td>
                    <td style="padding: 8px; border-bottom: 1px solid #e9ecef; font-family: monospace;">${escapeHtml(item.frame_number)}</td>
                    <td style="padding: 8px; border-bottom: 1px solid #e9ecef;">${formatDate(item.transfer_date)}</td>
                    <td style="padding: 8px; border-bottom: 1px solid #e9ecef; background-color: #17a2b8; color: white; border-radius: 3px; text-align: center;">${escapeHtml(item.transferred_to)}</td>
                    <td style="padding: 8px; border-bottom: 1px solid #e9ecef; text-align: right; font-weight: bold;">${formatCurrency(item.inventory_cost)}</td>
                </tr>
            `;
        });

        html += `
                    </tbody>
                    <tfoot>
                        <tr style="background-color: #e9ecef;">
                            <td colspan="9" style="padding: 10px; text-align: right; font-weight: bold;">Total:</td>
                            <td style="padding: 10px; text-align: right; font-weight: bold;">${formatCurrency(totalInventoryCost)}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        `;
    }

    html += `</div>`;

    const container = document.createElement("div");
    container.innerHTML = html;

    const opt = {
        margin: 0.5,
        filename: `Monthly_Transferred_Summary_${currentReportMonth}_${currentReportBranch}.pdf`,
        image: { type: "jpeg", quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { unit: "in", format: "letter", orientation: "landscape" }
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
                    brandColor = 'text-danger'; // Red
                    break;
                case 'yamaha':
                    brandColor = 'text-dark'; // Black
                    break;
                case 'suzuki':
                    brandColor = 'text-primary'; // Blue
                    break;
                case 'kawasaki':
                    brandColor = 'text-success'; // Green
                    break;
                default:
                    brandColor = 'text-secondary';
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
