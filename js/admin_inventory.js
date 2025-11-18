


let selectedReportModels = [];
let allModelsCache = [];
let currentReportModel = null;
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
let currentReportSaleType = null;
let currentReportCategory = null;
let currentReportBrand = null;
let modelCount = 0;
let currentUserRole = "USER";
const canAccessScrapFeature = isHeadOffice || isAdminUser;

let managingTransfer = {
  invoiceNumber: null,
  fromBranch: null,
  toBranch: null,
  transferDate: null,
  notes: null,
  initialItems: [], 
  itemsToAdd: [], 
  itemsToRemove: [], 
};

/**
 * Converts full branch names to shorter codes for PDF reports.
 * @param {string} branchName The full name of the branch.
 * @returns {string} The abbreviated branch code.
 */
function getBranchShortcut(branchName) {
  const shortcuts = {
    HEADOFFICE: "HO",
    KINGDOM: "KING",
    TANQUE: "TANQ",
    DFISHER: "DFIS",
    "ROXAS SUZUKI": "RXS-S",
    "ROXAS HONDA": "RXS-H",
    MAMBUSAO: "MAMB",
    SIGMA: "SIG",
    PRC: "PRC",
    BAILAN: "BAIL",
    CUARTERO: "CUAR",
    JAMINDAN: "JAM",
    "ANTIQUE-1": "ANT-1",
    "ANTIQUE-2": "ANT-2",
    "DELGADO HONDA": "SDH",
    "DELGADO SUZUKI": "SDS",
    "JARO-1": "JAR-1",
    "JARO-2": "JAR-2",
    "KALIBO MABINI": "SKM",
    "KALIBO SUZUKI": "SKS",
    ALTAVAS: "ALT",
    EMAP: "EMP",
    CULASI: "CUL",
    BACOLOD: "BAC",
    "PASSI-1": "PAS-1",
    "PASSI-2": "PAS-2",
    BALASAN: "BAL",
    GUIMARAS: "GUI",
    "PEMDI BACOLOD": "PEMDI",
    "EEMSI-GUIMARAS": "EEMSI",
    "INFINITY BACOLOD": "INF",
    AJUY: "AJY",
    "MINDORO ROXAS": "MDR",
    "3S MINDORO": "M3S",
    "MINDORO-MB": "MINMB",
    "MINDORO MANSALAY": "MAN",
    "K-RIDERS ROXAS": "K-RID",
    IBAJAY: "IBA",
    NUMANCIA: "NUM",
    CFCIPRC: "CFC",
  };
  return shortcuts[branchName.toUpperCase()] || branchName;
}

const reportOptionsConfig = {
  inventory: {
    periods: ["monthly", "as_of_date"],
    filters: ["branch", "category", "brand", "model"],
  },
  inventory_summary: {
    periods: ["monthly", "as_of_date"],
    filters: ["branch", "category", "brand", "model"],
  },
  transferred: {
    periods: ["daily", "monthly", "custom_range"],
    filters: ["branch", "category", "brand", "model"],
  },
  received: {
    periods: ["daily", "monthly", "custom_range"],
    filters: ["branch", "category", "brand", "model"],
  },
  delivered_stocks: {
    periods: ["daily", "monthly", "custom_range"],
    filters: ["branch", "category", "brand", "model"],
  },
  motorcycle: {
    periods: ["monthly"],
    filters: ["branch", "category", "brand", "model"],
  },
  sold_units: {
    periods: ["daily", "monthly", "custom_range"],
    filters: ["branch", "category", "brand", "model", "sale_type"],
  },
  scrapped: {
    periods: ["daily", "monthly", "custom_range"],
    filters: ["branch", "category", "brand", "model"],
  },
  redeemed: {
    periods: ["daily", "monthly", "custom_range"],
    filters: ["branch", "category", "brand", "model"],
  },
};

const pdfStyles = `
    @media print {
        body * {
            visibility: hidden;
        }
        #monthlyReportPrintContainer, #monthlyReportPrintContainer * {
            visibility: visible;
        }
        #monthlyReportPrintContainer {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
        .table-container {
            overflow: visible !important;
        }
        table {
            page-break-inside: auto !important;
        }
        tr {
            page-break-inside: avoid !important;
            page-break-after: auto !important;
        }
    }
`;

const manageTransferStyles = `
    .list-container .transfer-item { display: flex; justify-content: space-between; align-items: center; padding: 8px; border-bottom: 1px solid #eee; }
    .list-container .transfer-item:last-child { border-bottom: none; }
    .transfer-item.to-be-removed { background-color: #ffe5e5; text-decoration: line-through; opacity: 0.7; }
    .transfer-item.to-be-added { background-color: #e5f5e5; }
`;





$(document).ready(function () {
  
  const styleSheet = document.createElement("style");
  styleSheet.textContent = pdfStyles;
  document.head.appendChild(styleSheet);
  const styleSheetManage = document.createElement("style");
  styleSheetManage.textContent = manageTransferStyles;
  document.head.appendChild(styleSheetManage);

  
  shownTransferIds = [];
  loadInventoryDashboard();
  loadInventoryTable();
  setupEventListeners();
  $("#reportType").trigger("change");

  loadTransfers(
    "in-transit",
    "#in-transitTransfersBody",
    "#in-transitTransfersPagination",
    "#inTransitCount"
  );
  loadTransfers(
    "completed",
    "#completedTransfersBody",
    "#completedTransfersPagination",
    "#completedCount"
  );
  loadTransfers(
    "rejected",
    "#rejectedTransfersBody",
    "#rejectedTransfersPagination",
    "#rejectedCount"
  );

  
  $('button[data-bs-toggle="tab"]').on("shown.bs.tab", function (e) {
    const target = $(e.target).data("bs-target");
    switch (target) {
      case "#soldUnits":
        loadSoldUnits(1);
        break;
      case "#repossessedUnits":
        loadRepossessedUnits(1);
        break;
      case "#scrappedUnits":
        loadScrappedUnits(1);
        break;
      case "#redeemedUnits":
        loadRedeemedUnits(1);
        break;
      case "#activityLog":
        loadActivityLog(1);
        break;
      case "#directShipments":  // NEW: Load direct shipments when tab is shown
      loadDirectShipments(1);
      break;
      
    }
  });

  
  $("#soldUnitsSearchBtn").on("click", () =>
    loadSoldUnits(1, $("#soldUnitsSearch").val())
  );
  $("#repoUnitsSearchBtn").on("click", () =>
    loadRepossessedUnits(1, $("#repoUnitsSearch").val())
  );
  $("#scrappedUnitsSearchBtn").on("click", () =>
    loadScrappedUnits(1, $("#scrappedUnitsSearch").val())
  );
  $("#redeemedUnitsSearchBtn").on("click", () =>
    loadRedeemedUnits(1, $("#redeemedUnitsSearch").val())
  );

  
  $(document).on("show.bs.modal", ".modal", function () {
    const zIndex = 1050 + 10 * $(".modal:visible").length;
    $(this).css("z-index", zIndex);
    setTimeout(() => {
      $(".modal-backdrop")
        .not(".modal-stack")
        .css("z-index", zIndex - 1)
        .addClass("modal-stack");
    }, 0);
  });

  $(document).on("hidden.bs.modal", ".modal", function () {
    $(".modal:visible").length && $(document.body).addClass("modal-open");
    if ($(".modal:visible").length === 0) {
      $(".modal-backdrop").remove();
    }
  });

  $(document).on("hidden.bs.modal", function () {
    if ($(".modal.show").length === 0) {
      $("body").removeClass("modal-open").css("padding-right", "");
    }
  });

  
  $("#reportType").trigger("change");
});


$('button[data-bs-toggle="tab"]').on("shown.bs.tab", function (e) {
  const target = $(e.target).data("bs-target");
  switch (target) {
    case "#soldUnits":
      loadSoldUnits(1);
      break;
    case "#repossessedUnits":
      loadRepossessedUnits(1);
      break;
    case "#scrappedUnits":
      loadScrappedUnits(1);
      break;
    case "#redeemedUnits":
      loadRedeemedUnits(1);
      break;
  }
});


$("#soldUnitsSearchBtn").on("click", () =>
  loadSoldUnits(1, $("#soldUnitsSearch").val())
);
$("#repoUnitsSearchBtn").on("click", () =>
  loadRepossessedUnits(1, $("#repoUnitsSearch").val())
);
$("#scrappedUnitsSearchBtn").on("click", () =>
  loadScrappedUnits(1, $("#scrappedUnitsSearch").val())
);
$("#redeemedUnitsSearchBtn").on("click", () =>
  loadRedeemedUnits(1, $("#redeemedUnitsSearch").val())
);


$(
  "#soldUnitsSearch, #repoUnitsSearch, #scrappedUnitsSearch, #redeemedUnitsSearch"
).on("keypress", function (e) {
  if (e.which === 13) $(this).next("button").click();
});


$(document).on("click", ".revert-btn", function () {
  const id = $(this).data("id");
  const type = $(this).data("type");
  $("#revertId").val(id);
  $("#revertType").val(type);
  $("#revertMessage").html(
    `Are you sure you want to revert this <strong>${type.toUpperCase()}</strong> transaction? This action will restore the unit to its previous state.`
  );
  $("#revertConfirmationModal").modal("show");
});


$("#confirmRevertBtn").on("click", revertTransaction);
/**
 * Central function to set up all event listeners for the page.
 */
function setupEventListeners() {
  
  $("body").on(
    "input",
    ".engine-number, #editEngineNumber, .frame-number, #editFrameNumber",
    function () {
      this.value = this.value.toUpperCase();
    }
  );

  
  $('button[data-bs-target="#globalTransferHistory"]').on(
    "shown.bs.tab",
    function () {
      $("#globalTransferSearchBtn").click(); 
    }
  );

  $("#manageTransferSearch").on("keyup", function (e) {
    if (e.which === 13) {
      
      searchAvailableForTransfer();
    }
  });
  $("#manageTransferSearchBtn").on("click", searchAvailableForTransfer);
  $("#saveTransferChangesBtn").on("click", submitTransferUpdate);
  
  $("#directShipmentsSearchBtn").on("click", () =>
  loadDirectShipments(1, $("#directShipmentsSearch").val())
);
$("#directShipmentsSearch").on("keypress", function (e) {
  if (e.which === 13) {
    $("#directShipmentsSearchBtn").click();
  }
});

  
  $("#globalTransferSearchBtn").on("click", function () {
    const searchTerm = $("#globalTransferSearch").val();
    loadTransfers(
      "in-transit",
      "#in-transitTransfersBody",
      "#in-transitTransfersPagination",
      "#inTransitCount",
      searchTerm,
      1
    );
    loadTransfers(
      "completed",
      "#completedTransfersBody",
      "#completedTransfersPagination",
      "#completedCount",
      searchTerm,
      1
    );
    loadTransfers(
      "rejected",
      "#rejectedTransfersBody",
      "#rejectedTransfersPagination",
      "#rejectedCount",
      searchTerm,
      1
    );
  });
  $("#globalTransferSearch").on("keypress", function (e) {
    if (e.which === 13) {
      $("#globalTransferSearchBtn").click();
    }
  });

  $("#activityLogSearchBtn").on("click", () =>
    loadActivityLog(1, $("#activityLogSearch").val())
  );
  $(
    "#soldUnitsSearch, #repoUnitsSearch, #scrappedUnitsSearch, #redeemedUnitsSearch"
  ).on("keypress", function (e) {
    if (e.which === 13) $(this).next("button").click();
  });
  $("#activityLogSearch").on("keypress", function (e) {
    if (e.which === 13) $(this).next("button").click();
  });
  
  $(document).on("click", ".transfer-pagination .page-link", function (e) {
    e.preventDefault();
    if ($(this).parent().hasClass("disabled")) return;
    const page = $(this).data("page");
    const status = $(this).closest("ul").data("status");
    const searchTerm = $("#globalTransferSearch").val();

    if (status === "in-transit") {
      loadTransfers(
        "in-transit",
        "#in-transitTransfersBody",
        "#in-transitTransfersPagination",
        "#inTransitCount",
        searchTerm,
        page
      );
    } else if (status === "completed") {
      loadTransfers(
        "completed",
        "#completedTransfersBody",
        "#completedTransfersPagination",
        "#completedCount",
        searchTerm,
        page
      );
    } else if (status === "rejected") {
      loadTransfers(
        "rejected",
        "#rejectedTransfersBody",
        "#rejectedTransfersPagination",
        "#rejectedCount",
        searchTerm,
        page
      );
    }
  });

  
  $("#deleteTransferConfirmationModal").on("show.bs.modal", function (event) {
    const button = $(event.relatedTarget);
    const transferId = button.data("transfer-id");
    $(this).find("#transferToDeleteId").val(transferId);
  });

  $("#confirmDeleteTransferBtn").on("click", function () {
    const transferId = $("#transferToDeleteId").val();
    if (transferId) {
      deleteTransfer(transferId);
    }
  });

  
  $("#reportType").on("change", updateReportFilterOptions);

  
  const today = new Date().toISOString().slice(0, 10);
  $("#dailyDate").val(today);
  $("#asOfDate").val(today);
  $("#startDate").val(today);
  $("#endDate").val(today);
  const currentMonth = new Date().toISOString().slice(0, 7);
  $("#reportMonth").val(currentMonth);

  $(document).on("change", "#editPaymentType", function () {
    togglePaymentTypeDetails($(this).val());
  });

  $("#editStatus").change(function () {
    const status = $(this).val();
    toggleSoldDetails(status, null);
  });
  
  $("#searchModelBtn").click(searchModels);
  $("#searchModel").keypress((e) => {
    if (e.which === 13) searchModels();
  });

  $("#searchInventoryBtn").click(() => {
    currentInventoryQuery = $("#searchInventory").val();
    currentInventoryPage = 1;
    loadInventoryTable(
      currentInventoryPage,
      currentInventorySort,
      currentInventoryQuery
    );
  });
  $("#searchInventory").keypress(function (e) {
    if (e.which == 13) {
      currentInventoryQuery = $(this).val();
      currentInventoryPage = 1;
      loadInventoryTable(
        currentInventoryPage,
        currentInventorySort,
        currentInventoryQuery
      );
    }
  });

  $("#searchInvoiceNumberModal").on("hidden.bs.modal", function () {
    $("#invoiceNumberSearch").val("");
    $("#invoiceSearchResults").empty();
    $("#invoiceSearchResultsContainer").hide();
  });

  $(document).on("blur", ".frame-number", function () {
    checkFrameNumber($(this).val(), $(this));
  });

  $(document).on("blur", "#editFrameNumber", function () {
    const excludeId = $("#editId").val();
    checkFrameNumber($(this).val(), $(this), excludeId);
  });

  $(document).on("blur", ".engine-number", function () {
    checkEngineNumber($(this).val(), $(this));
  });

  $(document).on("blur", "#editEngineNumber", function () {
    const excludeId = $("#editId").val();
    checkEngineNumber($(this).val(), $(this), excludeId);
  });
  $("#searchDashboardBtn").click(() =>
    loadInventoryDashboard($("#searchDashboard").val())
  );
  $("#searchDashboard").keypress((e) => {
    if (e.which == 13) loadInventoryDashboard($(this).val());
  });

  
  $("#searchInvoiceNumberBtn").click(() =>
    $("#searchInvoiceNumberModal").modal("show")
  );
  $("#searchInvoiceBtn").click(searchInvoiceNumber);
  $("#invoiceNumberSearch").keypress((e) => {
    if (e.which === 13) {
      searchInvoiceNumber();
      e.preventDefault();
    }
  });

  $("#searchTransferReceiptBtn").click(() =>
    $("#searchTransferReceiptModal").modal("show")
  );
  $("#searchTransferBtn").click(searchTransferReceipt);
  $("#transferInvoiceSearch").keypress((e) => {
    if (e.which === 13) {
      searchTransferReceipt();
      e.preventDefault();
    }
  });

  
  $("#selectAllTransfers, #selectAllTransfersHeader").change(function () {
    const isChecked = $(this).prop("checked");
    $("#selectAllTransfers, #selectAllTransfersHeader").prop(
      "checked",
      isChecked
    );
    $(".transfer-checkbox").prop("checked", isChecked).trigger("change");
  });

  $(document).on("change", ".transfer-checkbox", function () {
    const transferId = $(this).val();
    if ($(this).prop("checked")) {
      if (!selectedTransferIds.includes(transferId)) {
        selectedTransferIds.push(transferId);
      }
    } else {
      selectedTransferIds = selectedTransferIds.filter(
        (id) => id !== transferId
      );
    }
    const total = $(".transfer-checkbox").length;
    const checked = $(".transfer-checkbox:checked").length;
    $("#selectAllTransfers, #selectAllTransfersHeader").prop(
      "checked",
      total > 0 && checked === total
    );
    updateTransferSelection();
  });

  $(document).on("click", ".transfer-row", function (e) {
    if (e.target.type !== "checkbox") {
      const checkbox = $(this).find(".transfer-checkbox");
      checkbox.prop("checked", !checkbox.prop("checked")).trigger("change");
    }
  });

  $(document)
    .off("click", "#acceptSelectedBtn")
    .on("click", "#acceptSelectedBtn", function (e) {
      e.preventDefault();
      if (selectedTransferIds.length === 0) {
        showErrorModal("Please select at least one transfer to accept");
        return;
      }
      showConfirmationModal(
        `Are you sure you want to accept ${selectedTransferIds.length} selected transfer(s)? These motorcycles will be added to your branch inventory.`,
        "Accept Selected Transfers",
        acceptSelectedTransfers,
        "success",
        "Accept Transfers"
      );
    });

  $(document)
    .off("click", "#rejectSelectedBtn")
    .on("click", "#rejectSelectedBtn", function (e) {
      e.preventDefault();
      if (selectedTransferIds.length === 0) {
        showErrorModal("Please select at least one transfer to reject");
        return;
      }
      showConfirmationModal(
        `Are you sure you want to reject ${selectedTransferIds.length} selected transfer(s)? This action cannot be undone.`,
        "Reject Selected Transfers",
        rejectSelectedTransfers,
        "danger",
        "Reject Transfers"
      );
    });

  
  $(document).on("click", ".page-link", function (e) {
    e.preventDefault();
    if ($(this).parent().hasClass("disabled")) return;
    const oldPage = currentInventoryPage;
    if ($(this).attr("id") === "prevPage") {
      currentInventoryPage = Math.max(1, currentInventoryPage - 1);
    } else if ($(this).attr("id") === "nextPage") {
      currentInventoryPage = Math.min(
        totalInventoryPages,
        currentInventoryPage + 1
      );
    } else {
      currentInventoryPage = parseInt($(this).data("page"));
    }
    if (currentInventoryPage !== oldPage) {
      loadInventoryTable(
        currentInventoryPage,
        currentInventorySort,
        currentInventoryQuery
      );
    }
  });

  $(document).on("click", ".sortable-header", function () {
    const sortField = $(this).data("sort");
    currentInventorySort =
      currentInventorySort === sortField + "_asc"
        ? sortField + "_desc"
        : sortField + "_asc";
    loadInventoryTable(
      currentInventoryPage,
      currentInventorySort,
      currentInventoryQuery
    );
  });

  

  
  $("#editMotorcycleForm").submit((e) => {
    e.preventDefault();
    updateMotorcycle();
  });

  
  $("#paymentType").change(handlePaymentTypeChange);
  $("#editPaymentType").change(function () {
    togglePaymentTypeDetails($(this).val());
  });
  $("#editStatus").change(function () {
    toggleSoldDetails($(this).val(), null);
  });

  
  $("#generateReportsButton").click(showMonthlyReportOptions);
  $("#generateReportBtn").off("click").on("click", generateReport);
  $(document).on("click", "#exportMonthlyReportToPDF", () =>
    generateReportPDF()
  );
  $('input[name="reportPeriod"]').on("change", function () {
    if ($(this).val() === "as_of_date") {
      $("#monthPickerContainer").hide();
      $("#datePickerContainer").show();
    } else {
      
      $("#monthPickerContainer").show();
      $("#datePickerContainer").hide();
    }
  });

  
  $("#reportModelSearch").on("keyup", function () {
    updateModelSearchResults($(this).val());
  });

  
  $("#model-search-results").on("click", ".dropdown-item", function (e) {
    e.stopPropagation();
  });
  $("#reportModelSearch").on("click", function (e) {
    e.stopPropagation();
  });

  

  $("#redeemPaymentType").change(handleRedeemPaymentTypeChange);
  $("#submitRedeemBtn").click(submitRedeem);

  $(document).on("blur", ".engine-number", function () {
    checkEngineNumber($(this).val(), $(this));
  });
  $(document).on("blur", "#editEngineNumber", function () {
    checkEngineNumber($(this).val(), $(this), $("#editId").val());
  });
  $(document).on("blur", ".frame-number", function () {
    checkFrameNumber($(this).val(), $(this));
  });
  $(document).on("blur", "#editFrameNumber", function () {
    checkFrameNumber($(this).val(), $(this), $("#editId").val());
  });

  $("#reportType").on("change", function () {
    const selectedReport = $(this).val();

    
    $(
      "#reportPeriodContainer, #datePickerContainer, #soldSaleTypeContainer, #brandFilterContainer, #dailyReportDateContainer"
    ).hide();
    $("#monthPickerContainer").show(); 
    $("#periodMonthly").prop("checked", true); 

    
    if (selectedReport === "inventory") {
      $("#reportPeriodContainer").show();
      $("#brandFilterContainer").show();
      $('input[name="reportPeriod"]:checked').trigger("change"); 
    }
    
    else if (selectedReport === "sold_units") {
      $("#reportPeriodContainer").show();
      $("#brandFilterContainer").show();
      $("#soldSaleTypeContainer").show();
      $('input[name="reportPeriod"]:checked').trigger("change"); 
    }
    
    else if (selectedReport === "daily_sold_units") {
      $("#monthPickerContainer").hide();
      $("#datePickerContainer").show(); 
      $("#soldSaleTypeContainer").show();
      $("#brandFilterContainer").show();
    }
    
    else if (
      ["transferred", "received", "scrapped", "motorcycle"].includes(
        selectedReport
      )
    ) {
      $("#brandFilterContainer").show();
    }
  });
  
  $('input[name="reportPeriod"]').on("change", function () {
    
    $("#datePickerDailyContainer").hide();
    $("#monthPickerContainer").hide();
    $("#datePickerAsOfContainer").hide();
    $("#dateRangeContainer").hide();

    
    const selectedPeriod = $(this).val();
    if (selectedPeriod === "daily") {
      $("#datePickerDailyContainer").show();
    } else if (selectedPeriod === "monthly") {
      $("#monthPickerContainer").show();
    } else if (selectedPeriod === "as_of_date") {
      $("#datePickerAsOfContainer").show();
    } else if (selectedPeriod === "custom") {
      $("#dateRangeContainer").show();
    }
  });

  $(document).ready(function () {
    $("#reportType").trigger("change");
  });

  
  $('button[data-bs-target="#globalTransferHistory"]').on(
    "shown.bs.tab",
    function () {
      const searchTerm = $("#globalTransferSearch").val();
      loadTransfers(
        "in-transit",
        "#in-transitTransfersBody",
        "#in-transitTransfersPagination",
        "#inTransitCount",
        searchTerm
      );
      loadTransfers(
        "completed",
        "#completedTransfersBody",
        "#completedTransfersPagination",
        "#completedCount",
        searchTerm
      );
      loadTransfers(
        "rejected",
        "#rejectedTransfersBody",
        "#rejectedTransfersPagination",
        "#rejectedCount",
        searchTerm
      );
    }
  );

  
  $("#globalTransferSearchBtn").on("click", function () {
    const searchTerm = $("#globalTransferSearch").val();
    loadTransfers(
      "in-transit",
      "#in-transitTransfersBody",
      "#in-transitTransfersPagination",
      "#inTransitCount",
      searchTerm,
      1
    );
    loadTransfers(
      "completed",
      "#completedTransfersBody",
      "#completedTransfersPagination",
      "#completedCount",
      searchTerm,
      1
    );
    loadTransfers(
      "rejected",
      "#rejectedTransfersBody",
      "#rejectedTransfersPagination",
      "#rejectedCount",
      searchTerm,
      1
    );
  });
  $("#globalTransferSearch").on("keypress", function (e) {
    if (e.which === 13) {
      $("#globalTransferSearchBtn").click();
    }
  });

  
  $(document).on("click", ".transfer-pagination .page-link", function (e) {
    e.preventDefault();
    if ($(this).parent().hasClass("disabled")) return;
    const page = $(this).data("page");
    const status = $(this).closest("ul").data("status");
    const searchTerm = $("#globalTransferSearch").val();

    
    if (status === "in-transit") {
      loadTransfers(
        "in-transit",
        "#in-transitTransfersBody",
        "#in-transitTransfersPagination",
        "#inTransitCount",
        searchTerm,
        page
      );
    } else if (status === "completed") {
      loadTransfers(
        "completed",
        "#completedTransfersBody",
        "#completedTransfersPagination",
        "#completedCount",
        searchTerm,
        page
      );
    } else if (status === "rejected") {
      loadTransfers(
        "rejected",
        "#rejectedTransfersBody",
        "#rejectedTransfersPagination",
        "#rejectedCount",
        searchTerm,
        page
      );
    }
  });
}





function showSuccessModal(message) {
  $("#successMessage").text(message);
  $("#successModal").modal("show");
  setTimeout(() => $("#successModal").modal("hide"), 2000);
}

function showErrorModal(message) {
  $("#errorMessage").text(message);
  $("#errorModal").modal("show");
  setTimeout(() => $("#errorModal").modal("hide"), 3000);
}

function showInfoModal(message) {
  $("#infoMessage").text(message);
  $("#infoModal").modal("show");
  setTimeout(() => $("#infoModal").modal("hide"), 3000);
}

function showConfirmationModal(
  message,
  title,
  callback,
  btnClass = "primary",
  btnText = "Confirm"
) {
  $("#confirmationMessage").html(message); 
  $("#confirmationModalLabel").text(title);
  const confirmBtn = $("#confirmActionBtn");
  confirmBtn
    .text(btnText)
    .removeClass("btn-primary btn-success btn-danger")
    .addClass(`btn-${btnClass}`);
  const modal = $("#confirmationModal");

  confirmBtn.off("click").on("click", function () {
    modal.modal("hide");
    if (typeof callback === "function") {
      callback();
    }
  });

  modal.modal("show");
}

function ensureModalScrollable(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.style.overflowY = "auto";
    const modalBody = modal.querySelector(".modal-body");
    if (modalBody) {
      modalBody.style.overflowY = "auto";
      modalBody.style.maxHeight = "calc(100vh - 200px)";
    }
  }
}







function loadInventoryDashboard(
  searchTerm = "",
  sortBy = "model",
  sortOrder = "asc"
) {
  $("#inventoryCards").html(
    '<div class="col-12 text-center py-5"><div class="spinner-border text-black" role="status"><span class="visually-hidden">Loading...</span></div></div>'
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
            valueA = (a.model || "").toLowerCase(); 
            valueB = (b.model || "").toLowerCase(); 
          } else if (sortBy === "brand") {
            valueA = (a.brand || "").toLowerCase(); 
            valueB = (b.brand || "").toLowerCase(); 
          } else {
            valueA = (a.model || "").toLowerCase(); 
            valueB = (b.model || "").toLowerCase(); 
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

    const sortedBrands = Object.keys(brands).sort();

    sortedBrands.forEach((brand) => {
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

      html += `
        <div class="col-12 mb-3">
          <div class="brand-header p-3 ${brandColor}" style="border-radius: 8px; margin-bottom: 15px;">
            <h5 class="mb-0 fw-bold text-uppercase" style="color: #333; letter-spacing: 1px;">
              ${brand} <span class="badge bg-dark ms-2">${brands[brand].length} models</span>
            </h5>
          </div>
        </div>
      `;

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
    '<tr><td colspan="11" class="text-center py-5"><div class="spinner-border text-black" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>'
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

function renderInventoryTable(data) {
  let html = "";

  if (data.length === 0) {
    html =
      '<tr><td colspan="12" class="text-center py-5 text-muted">No inventory data found</td></tr>';
  } else {
    data.forEach((item) => {
      let categoryBadge = "";
      if (item.category === "brandnew") {
        categoryBadge = '<span class="badge bg-success">BRANDNEW</span>';
      } else if (item.category === "repo") {
        categoryBadge = '<span class="badge bg-warning text-dark">REPO</span>';
      }

      html += `
        <tr data-id="${item.id}">
          <td>${item.invoice_number || "N/A"}</td>
          <td>${
            item.date_received
              ? formatDate(item.date_received)
              : formatDate(item.date_delivered)
          }</td>
          <td>${formatCurrency(item.inventory_cost)}</td>
          <td>${item.brand}</td>
          <td>${item.model}</td>
          <td>${categoryBadge}</td>
          <td>${item.engine_number}</td>
          <td>${item.frame_number}</td>
          <td>${item.color}</td>
          <td>${item.current_branch}</td>
          <td>
            <div class="btn-group btn-group-sm">
              <button class="btn btn-outline-primary edit-btn" title="Edit Motorcycle">
                <i class="bi bi-pencil"></i>
              </button>

              <button class="btn btn-outline-info history-btn" title="View Transfer History">
            <i class="bi bi-clock-history"></i>
        </button>
             
        <button class="btn btn-outline-dark delete-btn" title="Delete Record">
            <i class="bi bi-trash"></i>
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

function setupTableActionButtons() {
  
  
  $("#inventoryTableBody")
    .off("click")
    .on("click", "button", function () {
      const button = $(this);
      const id = button.closest("tr").data("id");

      if (button.hasClass("edit-btn")) {
        loadMotorcycleForEdit(id);
      } else if (button.hasClass("history-btn")) {
        if (id) {
          loadUnitMovement(id);
        }
      } else if (button.hasClass("sell-btn")) {
        sellMotorcycle(id);
      } else if (button.hasClass("repo-btn")) {
        openRepoModal(id);
      } else if (button.hasClass("redeem-btn")) {
        openRedeemModal(id);
      } else if (button.hasClass("scrap-btn")) {
        scrapMotorcycle(id);
      } else if (button.hasClass("delete-btn")) {
        showConfirmationModal(
          "Are you sure you want to <strong>permanently delete</strong> this motorcycle record?",
          "Confirm Deletion",
          function () {
            deleteMotorcycle(id);
          },
          "danger",
          "Delete Permanently"
        );
      }
    });
}

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




function loadUnitMovement(id) {
  $.ajax({
    url: "../api/inventory_management.php",
    method: "GET",
    data: { action: "get_motorcycle_transfer_log", motorcycle_id: id },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        const details = response.details;
        const history = response.history || []; 

        
        $("#movementUnitTitle").text(`${details.brand} ${details.model}`);
        $("#movementUnitDetails").text(
          `Engine #: ${details.engine_number} | Frame #: ${details.frame_number}`
        );

        const tbody = $("#movementHistoryBody");
        tbody.empty();

        if (history.length > 0) {
          history.forEach((item) => {
            tbody.append(`
              <tr>
                <td>${formatDate(item.date)}</td>
                <td>${item.event}</td>
                <td>${item.from}</td>
                <td>${item.to}</td>
                <td>${item.status}</td>
                <td>${item.invoice || ""}</td>
              </tr>
            `);
          });
        } else {
          tbody.html(
            `<tr><td colspan="6" class="text-center text-muted">No movement history found for this unit.</td></tr>`
          );
        }

        $("#unitMovementModal").modal("show");
      } else {
        showErrorModal(response.message || "Error loading movement history");
      }
    },
    error: function (xhr, status, error) {
      showErrorModal("Error loading movement data: " + error);
    },
  });
}

function deleteMotorcycle(id) {
  $.ajax({
    url: "../api/inventory_management.php",
    method: "POST",
    data: {
      action: "delete_motorcycle",
      motorcycle_id: id,
    },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        showSuccessModal("Motorcycle record deleted successfully.");
        loadInventoryTable(
          currentInventoryPage,
          currentInventorySort,
          currentInventoryQuery
        );
        loadInventoryDashboard();
      } else {
        showErrorModal(
          response.message || "Failed to delete motorcycle record."
        );
      }
    },
    error: function () {
      showErrorModal("An error occurred while trying to delete the record.");
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
      include_sale_details: true,
    },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        const data = response.data;
        $("#editId").val(data.id);
        $("#editDateDelivered").val(formatDate(data.date_delivered));
        $("#editDateReceived").val(
          data.date_received ? formatDate(data.date_received) : ""
        );
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

        toggleSoldDetails(data.status, data.sale_details);
        const redeemContainer = $("#redeemInfoContainer");
        if (data.redeem_details) {
          $("#redeemInfoDate").text(
            formatDate(data.redeem_details.redeem_date)
          );
          $("#redeemInfoAmount").text(
            "₱" + formatCurrency(data.redeem_details.amount_paid)
          );
          redeemContainer.show();
        } else {
          redeemContainer.hide();
        }

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

function updateMotorcycle() {
  const status = $("#editStatus").val();

  
  const dateReceivedValue = $("#editDateReceived").val();
  const formattedDateReceived = dateReceivedValue
    ? formatDateForAPI(dateReceivedValue)
    : null; 

  const formData = {
    action: "update_motorcycle",
    id: $("#editId").val(),
    date_delivered: formatDateForAPI($("#editDateDelivered").val()),
    date_received: formattedDateReceived, 
    brand: $("#editBrand").val(),
    model: $("#editModel").val(),
    category: $("#editCategory").val(),
    engine_number: $("#editEngineNumber").val(),
    frame_number: $("#editFrameNumber").val(),
    invoice_number: $("#editInvoiceNumber").val(),
    color: $("#editColor").val(),
    inventory_cost: $("#editInventoryCost").val(),
    current_branch: $("#editCurrentBranch").val(),
    status: status,
  };

  if (status === "sold") {
    formData.sale_date = formatDateForAPI($("#editSaleDate").val());
    formData.customer_name = $("#editCustomerName").val();
    formData.payment_type = $("#editPaymentType").val();
    formData.dr_number = $("#editDrNumber").val();
    formData.cod_amount = $("#editCodAmount").val();
    formData.terms = $("#editTerms").val();
    formData.monthly_amortization = $("#editMonthlyAmortization").val();
  }

  
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
      console.log("Update Motorcycle Response:", response);

      if (response.console_message) {
        console.log("Backend Info:", response.console_message);
      }

      if (response.success) {
        $("#editMotorcycleModal").modal("hide");

        if (response.type === "existing_invoice") {
          showSuccessModal(response.message);
        } else if (response.type === "new_invoice") {
          showSuccessModal(response.message);
        } else {
          showSuccessModal(
            response.message || "Motorcycle updated successfully!"
          );
        }

        loadInventoryDashboard();
        loadInventoryTable(
          currentInventoryPage,
          currentInventorySort,
          currentInventoryQuery
        );
      } else {
        console.error("Update Motorcycle Error:", response.message);

        if (
          response.message.includes("DUPLICATE_ENGINE_NUMBER") ||
          response.message.includes("DUPLICATE_FRAME_NUMBER") ||
          response.message.includes("Missing required field")
        ) {
          showErrorModal(response.message);
        } else {
          showSuccessModal("Update completed. Check console for details.");
        }
      }
    },
    error: function (xhr, status, error) {
      console.error("AJAX Error:", {
        status: status,
        error: error,
        response: xhr.responseText,
      });
      showErrorModal("Connection error. Please try again.");
    },
  });
}

function toggleSoldDetails(status, saleDetails) {
  const soldDetailsContainer = $("#soldDetailsContainer");

  if (status === "sold") {
    soldDetailsContainer.show();

    if (saleDetails) {
      const saleDate =
        saleDetails.sale_date && saleDetails.sale_date !== "0000-00-00"
          ? saleDetails.sale_date
          : "";
      const customerName = saleDetails.customer_name || "";
      const paymentType = saleDetails.payment_type || "";
      const drNumber = saleDetails.dr_number || "";
      const codAmount =
        saleDetails.cod_amount != null ? saleDetails.cod_amount : "";
      const terms = saleDetails.terms != null ? saleDetails.terms : "";
      const monthlyAmortization =
        saleDetails.monthly_amortization != null
          ? saleDetails.monthly_amortization
          : "";

      $("#editSaleDate").val(formatDate(saleDate));
      $("#editCustomerName").val(customerName);
      $("#editPaymentType").val(paymentType);
      $("#editDrNumber").val(drNumber);
      $("#editCodAmount").val(codAmount);
      $("#editTerms").val(terms);
      $("#editMonthlyAmortization").val(monthlyAmortization);
    } else {
      $(
        "#editSaleDate, #editCustomerName, #editPaymentType, #editDrNumber, #editCodAmount, #editTerms, #editMonthlyAmortization"
      ).val("");
    }
    togglePaymentTypeDetails($("#editPaymentType").val());
  } else {
    soldDetailsContainer.hide();
  }
}

function togglePaymentTypeDetails(paymentType) {
  if (paymentType === "COD") {
    $("#codDetails").show();
    $("#installmentDetails").hide();
  } else if (paymentType === "Installment") {
    $("#codDetails").hide();
    $("#installmentDetails").show();
  } else {
    $("#codDetails").hide();
    $("#installmentDetails").hide();
  }
}




function searchModels() {
  const query = $("#searchModel").val().trim();
  if (query.length < 2) return;

  $("#modelList").html(
    '<div class="text-center py-3"><div class="spinner-border text-black" role="status"></div></div>'
  );

  $.get(
    "../api/inventory_management.php",
    {
      action: "search_inventory",
      query: query,
    },
    function (response) {
      if (
        response.success &&
        Array.isArray(response.data) &&
        response.data.length > 0
      ) {
        let html = "<h6>Search Results</h6>";

        const modelGroups = {};
        response.data.forEach((item) => {
          const modelKey = item.model?.trim() || "Unknown Model";
          if (!Array.isArray(modelGroups[modelKey])) {
            modelGroups[modelKey] = [];
          }
          modelGroups[modelKey].push(item);
        });

        Object.keys(modelGroups).forEach((model) => {
          const items = modelGroups[model];
          const first = items[0];
          const soldUnit = items.find(
            (unit) => unit.status.toLowerCase() === "sold"
          );

          let soldInfoHtml = "";
          if (soldUnit) {
            const saleDate = soldUnit.sale_date
              ? formatDate(soldUnit.sale_date)
              : "Sold";
            soldInfoHtml = `<span class="badge bg-danger">SOLD on ${saleDate}</span>`;
          } else {
            soldInfoHtml = `${items.length} unit(s) available`;
          }

          html += `
    <div class="card mb-2 model-item" data-model="${model}">
      <div class="card-body">
        <h6 class="card-title">${model}</h6>
        <p class="card-text small">
          ${first.color} · ${first.current_branch} <br>
          ${soldInfoHtml}
        </p>
      </div>
    </div>
  `;
        });

        $("#modelList").html(html);

        $(".model-item").click(function () {
          const model = $(this).data("model");
          const items = modelGroups[model] || [];
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
    const isSold = data.status.toLowerCase() === "sold";

    const saleInfoHtml = isSold
      ? `
      <hr>
      <h6 class='text-black'>Sale Information</h6>
      <div class='row'>
        <div class='col-md-6 mb-2'>
          <p><strong>Sale Date:</strong> ${
            data.sale_date ? formatDate(data.sale_date) : "N/A"
          }</p>
        </div>
        <div class='col-md-6 mb-2'>
          <p><strong>Customer Name:</strong> ${data.customer_name || "N/A"}</p>
        </div>
      </div>
      <div class='row'>
        <div class='col-md-6 mb-2'>
          <p><strong>Payment Type:</strong> ${data.payment_type || "N/A"}</p>
        </div>
      </div>

      <!-- COD Details -->
      ${
        data.payment_type === "COD"
          ? `
        <div class='row'>
          <div class='col-md-6 mb-2'>
            <p><strong>DR Number:</strong> ${data.dr_number || "N/A"}</p>
          </div>
          <div class='col-md-6 mb-2'>
            <p><strong>COD Amount:</strong> ${
              data.cod_amount ? formatCurrency(data.cod_amount) : "N/A"
            }</p>
          </div>
        </div>
      `
          : ""
      }

      <!-- Installment Details -->
      ${
        data.payment_type === "Installment"
          ? `
        <div class='row'>
          <div class='col-md-6 mb-2'>
            <p><strong>Terms (months):</strong> ${data.terms || "N/A"}</p>
          </div>
          <div class='col-md-6 mb-2'>
            <p><strong>Monthly Amortization:</strong> ${
              data.monthly_amortization
                ? formatCurrency(data.monthly_amortization)
                : "N/A"
            }</p>
          </div>
        </div>
      `
          : ""
      }
    `
      : "";

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
              <p><strong>Invoice Number/MT:</strong> ${
                data.invoice_number || "N/A"
              }</p>
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
                data.inventory_cost
                  ? formatCurrency(data.inventory_cost)
                  : "N/A"
              }</p>
            </div>
          </div>
          ${saleInfoHtml}
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


function viewModelDetails(units) {
  let html = "";

  
  
  units.forEach((data, index) => {
    const isSold = data.status.toLowerCase() === "sold";

   
    
    const redeemInfoHtml = data.redeem_details
      ? `
            <hr>
            <h6 class='text-success'>Redemption Information</h6>
            <div class='row'>
                <div class='col-md-6 mb-2'>
                    <p><strong>Redeemed On:</strong> ${formatDate(
                      data.redeem_details.redeem_date
                    )}</p>
                </div>
                <div class='col-md-6 mb-2'>
                    <p><strong>Amount Paid:</strong> ${formatCurrency(
                      data.redeem_details.amount_paid
                    )}</p>
                </div>
            </div>`
      : "";
   

    
    const saleInfoHtml = isSold
      ? `
            <hr>
            <h6 class='text-black'>Sale Information</h6>
            <div class='row'>
                <div class='col-md-6 mb-2'>
                    <p><strong>Sale Date:</strong> ${
                      data.sale_date ? formatDate(data.sale_date) : "N/A"
                    }</p>
                </div>
                <div class='col-md-6 mb-2'>
                    <p><strong>Customer Name:</strong> ${
                      data.customer_name || "N/A"
                    }</p>
                </div>
            </div>
            <div class='row'>
                <div class='col-md-6 mb-2'>
                    <p><strong>Payment Type:</strong> ${
                      data.payment_type || "N/A"
                    }</p>
                </div>
            </div>

            ${
              data.payment_type === "COD"
                ? `
                <div class='row'>
                    <div class='col-md-6 mb-2'>
                        <p><strong>DR Number:</strong> ${
                          data.dr_number || "N/A"
                        }</p>
                    </div>
                    <div class='col-md-6 mb-2'>
                        <p><strong>COD Amount:</strong> ${
                          data.cod_amount
                            ? formatCurrency(data.cod_amount)
                            : "N/A"
                        }</p>
                    </div>
                </div>`
                : ""
            }

            ${
              data.payment_type === "Installment"
                ? `
                <div class='row'>
                    <div class='col-md-6 mb-2'>
                        <p><strong>Terms (months):</strong> ${
                          data.terms || "N/A"
                        }</p>
                    </div>
                    <div class='col-md-6 mb-2'>
                        <p><strong>Monthly Amortization:</strong> ${
                          data.monthly_amortization
                            ? formatCurrency(data.monthly_amortization)
                            : "N/A"
                        }</p>
                    </div>
                </div>`
                : ""
            }`
      : "";

    
    html += `
            <div class="card mb-3">
                <div class="card-header">
                    Unit ${index + 1} of ${units.length}
                </div>
                <div class="card-body">
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
                            <p><strong>Status:</strong> 
                                <span class="badge ${getStatusClass(
                                  data.status
                                )}">
                                    ${
                                      data.status.charAt(0).toUpperCase() +
                                      data.status.slice(1)
                                    }
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-black">Identification & Pricing</h6>
                            <hr>
                            <p><strong>Engine #:</strong> ${
                              data.engine_number
                            }</p>
                            <p><strong>Frame #:</strong> ${
                              data.frame_number
                            }</p>
                            <p><strong>Inventory Cost:</strong> ${
                              data.inventory_cost
                                ? formatCurrency(data.inventory_cost)
                                : "N/A"
                            }</p>
                        </div>
                    </div>
                    ${redeemInfoHtml} 
                    ${saleInfoHtml}
                </div>
            </div>
        `;
  });

  $("#detailsModal .modal-body").html(html);
  $("#detailsModal").modal("show");
}







function sellMotorcycle(id) {
  $("#saleDate").datepicker("setDate", new Date());
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
    sale_date: formatDateForAPI($("#saleDate").val()),
    customer_name: $("#customerName").val(),
    payment_type: $("#paymentType").val(),
  };

  const saleDateInput = formData.sale_date;
  if (saleDateInput) {
    const saleDate = new Date(saleDateInput);
    const today = new Date();

    
    saleDate.setHours(0, 0, 0, 0);
    today.setHours(0, 0, 0, 0);

    if (saleDate > today) {
      showErrorModal(
        "Future dates are not allowed. Please select a valid sale date."
      );
      return; 
    }
  }

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
        loadInventoryDashboard();
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

function scrapMotorcycle(id) {
  $("#scrapMotorcycleId").val(id);
  $("#scrapDate").datepicker("setDate", new Date());
  $("#scrapReason").val("");
  $("#scrapMotorcycleModal").modal("show");
}


function submitScrap() {
  const formData = {
    action: "scrap_motorcycle",
    motorcycle_id: $("#scrapMotorcycleId").val(),
    scrap_date: formatDateForAPI($("#scrapDate").val()),
    scrap_reason: $("#scrapReason").val(),
  };

  if (!formData.scrap_date) {
    showErrorModal("Please select a scrap date");
    return;
  }

  $.ajax({
    url: "../api/inventory_management.php",
    method: "POST",
    data: formData,
    dataType: "json",
    success: function (response) {
      if (response.success) {
        $("#scrapMotorcycleModal").modal("hide");
        showSuccessModal("Motorcycle marked as scrapped successfully!");
        loadInventoryDashboard();
        loadInventoryTable(
          currentInventoryPage,
          currentInventorySort,
          currentInventoryQuery
        );
      } else {
        showErrorModal(
          response.message || "Error marking motorcycle as scrapped"
        );
      }
    },
    error: function (xhr, status, error) {
      showErrorModal("Error marking motorcycle as scrapped: " + error);
    },
  });
}

function handleRedeemPaymentTypeChange() {
  const paymentType = $("#redeemPaymentType").val();
  $("#redeemCodFields").hide();
  $("#redeemInstallmentFields").hide();

  if (paymentType === "COD") {
    $("#redeemCodFields").show();
  } else if (paymentType === "Installment") {
    $("#redeemInstallmentFields").show();
  }
}

/**
 * Opens the Redeem modal after checking if the unit is a valid repo unit.
 * It fetches historical sales data if available and pre-fills the form.
 * @param {number} id The ID of the motorcycle to be redeemed.
 */
function openRedeemModal(id) {
  $.ajax({
    url: "../api/inventory_management.php",
    method: "GET",
    data: {
      action: "get_motorcycle",
      id: id,
      include_sale_details: true, 
    },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        const motorcycle = response.data;
        
        if (
          motorcycle.category === "repo" &&
          motorcycle.status === "available"
        ) {
          $("#redeemMotorcycleId").val(id);
          $("#redeemForm")[0].reset();
          $("#redeemDate").val(new Date().toISOString().split("T")[0]);

          const saleDetails = motorcycle.sale_details;

          
          const fields = {
            saleDate: $("#redeemSaleDate"),
            customerName: $("#redeemCustomerName"),
            paymentType: $("#redeemPaymentType"),
            drNumber: $("#redeemDrNumber"),
            codAmount: $("#redeemCodAmount"),
            terms: $("#redeemTerms"),
            monthlyAmortization: $("#redeemMonthlyAmortization"),
          };

          if (saleDetails) {
            
            fields.saleDate.val(saleDetails.sale_date).prop("readonly", true);
            fields.customerName
              .val(saleDetails.customer_name)
              .prop("readonly", true);
            fields.paymentType
              .val(saleDetails.payment_type)
              .prop("disabled", true);

            if (saleDetails.payment_type === "COD") {
              fields.drNumber.val(saleDetails.dr_number).prop("readonly", true);
              fields.codAmount
                .val(saleDetails.cod_amount)
                .prop("readonly", true);
            } else if (saleDetails.payment_type === "Installment") {
              fields.terms.val(saleDetails.terms).prop("readonly", true);
              fields.monthlyAmortization
                .val(saleDetails.monthly_amortization)
                .prop("readonly", true);
            }
          } else {
            
            Object.values(fields).forEach(($field) => {
              $field.val("").prop("readonly", false).prop("disabled", false);
            });
          }

          handleRedeemPaymentTypeChange(); 
          $("#redeemModalLabel").text("Redeem Motorcycle");
          $("#redeemModal").modal("show");
        } else {
          showErrorModal(
            "This unit cannot be redeemed. It must be an 'available' unit with a 'repo' category."
          );
        }
      } else {
        showErrorModal(
          response.message || "Could not retrieve motorcycle details."
        );
      }
    },
    error: function () {
      showErrorModal(
        "An error occurred while checking the motorcycle's status."
      );
    },
  });
}

/**
 * Gathers data from the redeem modal and submits it to the backend via AJAX.
 */
function submitRedeem() {
  const formData = {
    action: "mark_as_redeem",
    motorcycle_id: $("#redeemMotorcycleId").val(),
    redeem_date: $("#redeemDate").val(),
    amount_paid: $("#redeemAmountPaid").val(),
    sale_date: $("#redeemSaleDate").val(),
    customer_name: $("#redeemCustomerName").val(),
    payment_type: $("#redeemPaymentType").val(),
  };

  
  if (
    !formData.redeem_date ||
    !formData.amount_paid ||
    !formData.sale_date ||
    !formData.customer_name ||
    !formData.payment_type
  ) {
    showErrorModal("Please fill in all required fields (*).");
    return;
  }

  if (formData.payment_type === "COD") {
    formData.dr_number = $("#redeemDrNumber").val();
    formData.cod_amount = $("#redeemCodAmount").val();
    if (!formData.dr_number || !formData.cod_amount) {
      showErrorModal("DR Number and COD Amount are required for COD payment.");
      return;
    }
  } else if (formData.payment_type === "Installment") {
    formData.terms = $("#redeemTerms").val();
    formData.monthly_amortization = $("#redeemMonthlyAmortization").val();
    if (!formData.terms || !formData.monthly_amortization) {
      showErrorModal(
        "Terms and Monthly Amortization are required for Installment payment."
      );
      return;
    }
  }

  $("#submitRedeemBtn")
    .prop("disabled", true)
    .html(
      '<span class="spinner-border spinner-border-sm"></span> Submitting...'
    );

  $.ajax({
    url: "../api/inventory_management.php",
    method: "POST",
    data: formData,
    dataType: "json",
    success: function (response) {
      if (response.success) {
        $("#redeemModal").modal("hide");
        showSuccessModal(
          response.message || "Motorcycle redeemed successfully!"
        );
        loadInventoryDashboard();
        loadInventoryTable(
          currentInventoryPage,
          currentInventorySort,
          currentInventoryQuery
        );
      } else {
        showErrorModal(response.message || "Error redeeming motorcycle.");
      }
    },
    error: function (xhr, status, error) {
      showErrorModal("An error occurred: " + error);
    },
    complete: function () {
      $("#submitRedeemBtn").prop("disabled", false).text("Confirm Redemption");
    },
  });
}
/**
 * Opens the REPO modal and populates it with the motorcycle ID.
 * @param {number} id The ID of the motorcycle to be marked as repo.
 */

function openRepoModal(id) {
  $("#repoMotorcycleId").val(id);
  
  $("#repoDate").val(new Date().toISOString().split("T")[0]);
  $("#repoReason").val("");
  $("#repoModal").modal("show");
}
/**
 * Submits the form to mark a motorcycle as repossessed.
 */
function submitRepo() {
  const formData = {
    action: "mark_as_repo",
    motorcycle_id: $("#repoMotorcycleId").val(),
    repo_date: formatDateForAPI($("#repoDate").val()),
    repo_reason: $("#repoReason").val(),
  };

  if (!formData.repo_date) {
    showErrorModal("Please select a repossession date.");
    return;
  }

  $.ajax({
    url: "../api/inventory_management.php",
    method: "POST",
    data: formData,
    dataType: "json",
    success: function (response) {
      if (response.success) {
        $("#repoModal").modal("hide");
        showSuccessModal(
          "Motorcycle successfully marked as REPO and returned to inventory."
        );
        loadInventoryDashboard();
        loadInventoryTable(
          currentInventoryPage,
          currentInventorySort,
          currentInventoryQuery
        );
      } else {
        showErrorModal(response.message || "Error marking motorcycle as REPO.");
      }
    },
    error: function (xhr, status, error) {
      showErrorModal("An error occurred: " + error);
    },
  });
}





function loadSoldUnits(page = 1, query = "") {
  loadGenericLogData(
    "get_sold_units",
    "#soldUnitsTableBody",
    "#soldUnitsPagination",
    renderSoldUnitsTable,
    "loadSoldUnits",
    page,
    query
  );
}
function loadRepossessedUnits(page = 1, query = "") {
  loadGenericLogData(
    "get_repossessed_units",
    "#repossessedUnitsTableBody",
    "#repossessedUnitsPagination",
    renderRepossessedUnitsTable,
    "loadRepossessedUnits",
    page,
    query
  );
}

function loadScrappedUnits(page = 1, query = "") {
  loadGenericLogData(
    "get_scrapped_units",
    "#scrappedUnitsTableBody",
    "#scrappedUnitsPagination",
    renderScrappedUnitsTable,
    "loadScrappedUnits",
    page,
    query
  );
}
function loadRedeemedUnits(page = 1, query = "") {
  loadGenericLogData(
    "get_redeemed_units",
    "#redeemedUnitsTableBody",
    "#redeemedUnitsPagination",
    renderRedeemedUnitsTable,
    "loadRedeemedUnits",
    page,
    query
  );
}

/**
 * Loads direct shipments data with pagination and optional search.
 * @param {number} page - The page number to load.
 * @param {string} query - The search query (e.g., by model or invoice).
 */
function loadDirectShipments(page = 1, query = "") {
  $("#directShipmentsTableBody").html(
    '<tr><td colspan="8" class="text-center py-5"><div class="spinner-border spinner-border-sm"></div></td></tr>'
  );

  $.ajax({
    url: "../api/inventory_management.php",
    method: "GET",
    data: { action: "get_direct_shipments", page, query },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        renderDirectShipmentsTable(response.data);
        renderGenericPagination(
          "#directShipmentsPagination",
          page,
          response.pagination.totalPages,
          "loadDirectShipments"
        );
      } else {
        $("#directShipmentsTableBody").html(
          '<tr><td colspan="8" class="text-center text-danger py-4">Error loading direct shipments</td></tr>'
        );
      }
    },
    error: function () {
      $("#directShipmentsTableBody").html(
        '<tr><td colspan="8" class="text-center text-danger py-4">Error loading direct shipments</td></tr>'
      );
    },
  });
}


/**
 * Renders the direct shipments table.
 * @param {Array} data - The shipments data from the API.
 */
function renderDirectShipmentsTable(data) {
    if (!data || data.length === 0) {
        $("#directShipmentsTableBody").html(
            '<tr><td colspan="8" class="text-center py-4">No direct shipments found.</td></tr>'
        );
        return;
    }

    let html = "";
    data.forEach((item) => {
        // Debug logging to check the actual data
        console.log("Item data:", item);
        console.log("Shipment date raw:", item.shipment_date);
        console.log("Formatted date:", formatDate(item.shipment_date));
        
        html += `<tr>
            <td>${escapeHtml(item.invoice_number || "N/A")}</td>
            <td>${formatDate(item.shipment_date)}</td>
            <td>${escapeHtml(item.brand)}</td>
            <td>${escapeHtml(item.model)}</td>
            <td><code>${escapeHtml(item.engine_number)}</code></td>
            <td><code>${escapeHtml(item.frame_number)}</code></td>
            <td>${escapeHtml(item.current_branch)}</td>
            <td class="text-end">
                <button class="btn btn-sm btn-warning edit-shipment-btn" 
                        data-id="${item.id}" 
                        title="Edit Invoice & Date">
                    <i class="bi bi-pencil"></i>
                </button>
            </td>
        </tr>`;
    });

    $("#directShipmentsTableBody").html(html);

    // Attach click handler for edit buttons
    $(".edit-shipment-btn").on("click", function () {
        const id = $(this).data("id");
        loadDirectShipmentForEdit(id);
    });
}
/**
 * Loads a direct shipment for editing (fetches fresh data from backend).
 * @param {number} id - The motorcycle/shipment ID.
 */
function loadDirectShipmentForEdit(id) {
  $.ajax({
    url: "../api/inventory_management.php",
    method: "GET",
    data: { action: "get_direct_shipment_for_edit", id },
    dataType: "json",
    success: function (response) {
      console.log("Edit response:", response);

      if (response.success) {
        const data = response.data;

        // ✅ FIXED: Use motorcycle_inventory ID, not invoice_id
        $("#editShipmentId").val(data.id); // This is the motorcycle_inventory.id
        $("#editShipmentDate").val(data.shipment_date ? formatDateForInput(data.shipment_date) : "");
        $("#editShipmentInvoice").val(data.invoice_number || "");

        $("#editShipmentModal").modal("show");
      } else {
        showErrorModal(response.message || "Error loading direct shipment data");
      }
    },
    error: function (xhr, status, error) {
      showErrorModal("Error loading direct shipment: " + error);
    },
  });
}

// Helper: convert "2025-12-24" -> "2025-12-24" (ISO format for <input type="date">)
function formatDateForInput(dateStr) {
  if (!dateStr) return "";
  const d = new Date(dateStr);
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, "0");
  const day = String(d.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
}


/**
 * Submits the edit form for a direct shipment.
 */
function submitEditShipment() {
  const id = $("#editShipmentId").val();
  const date = $("#editShipmentDate").val();
  const invoice = $("#editShipmentInvoice").val().trim();

  // Validate inputs
  if (!id) {
    showErrorModal("Invalid motorcycle ID. Please refresh and try again.");
    return;
  }

  if (!date) {
    showErrorModal("Shipment Date is required.");
    return;
  }

  if (!invoice) {
    showErrorModal("Invoice Number is required.");
    return;
  }

  // Show loading state
  const saveBtn = $("#saveEditShipmentBtn");
  const originalText = saveBtn.html();
  saveBtn.prop('disabled', true).html('<div class="spinner-border spinner-border-sm"></div> Saving...');

  $.ajax({
    url: "../api/inventory_management.php",
    method: "POST",
    data: {
      action: "update_direct_shipment",
      id: id, // This is motorcycle_inventory.id
      shipment_date: date, // Already in YYYY-MM-DD format for input type="date"
      invoice_number: invoice,
    },
    dataType: "json",
    success: function (response) {
      saveBtn.prop('disabled', false).html(originalText);
      
      if (response.success) {
        $("#editShipmentModal").modal("hide");
        showSuccessModal(response.message || "Direct shipment updated successfully!");
        
        // Reload the current page and search
        const currentPage = 1; // Or get current pagination state
        const currentQuery = $("#directShipmentsSearch").val();
        loadDirectShipments(currentPage, currentQuery);
      } else {
        showErrorModal(response.message || "Error updating shipment.");
      }
    },
    error: function (xhr, status, error) {
      saveBtn.prop('disabled', false).html(originalText);
      showErrorModal("Network error: " + error);
    },
  });
}

// Attach submit handler to the modal's save button
$("#saveEditShipmentBtn").on("click", submitEditShipment);

function loadGenericLogData(
  action,
  bodySelector,
  paginationSelector,
  renderFunction,
  paginationCallback,
  page,
  query
) {
  $(bodySelector).html(
    `<tr><td colspan="8" class="text-center py-5"><div class="spinner-border spinner-border-sm"></div></td></tr>`
  );
  $.ajax({
    url: "../api/inventory_management.php",
    method: "GET",
    data: { action, page, query },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        renderFunction(response.data);
        renderGenericPagination(
          paginationSelector,
          page,
          response.pagination.totalPages,
          paginationCallback
        );
      } else {
        $(bodySelector).html(
          `<tr><td colspan="8" class="text-center text-danger py-4">${response.message}</td></tr>`
        );
      }
    },
  });
}

function renderSoldUnitsTable(data) {
  let html = "";
  if (!data || data.length === 0) {
    $("#soldUnitsTableBody").html(
      '<tr><td colspan="7" class="text-center py-4">No sold units found.</td></tr>'
    );
    return;
  }
  data.forEach((item) => {
    html += `<tr>
            <td>${formatDate(item.sale_date)}</td>
            <td>${escapeHtml(item.customer_name)}</td>
            <td>${escapeHtml(item.model)}</td>
            <td><code>${escapeHtml(item.engine_number)}</code></td>
            <td>${escapeHtml(item.current_branch)}</td>
            <td><span class="badge bg-secondary">${escapeHtml(
              item.payment_type
            )}</span></td>
            <td class="text-end">
                <button class="btn btn-sm btn-warning revert-btn" data-id="${
                  item.id
                }" data-type="sold" title="Revert Sale"><i class="bi bi-arrow-counterclockwise"></i></button>
                <button class="btn btn-sm btn-info" onclick="loadMotorcycleForEdit(${
                  item.id
                })" title="Edit Sale Details"><i class="bi bi-pencil-square"></i></button>
            </td>
        </tr>`;
  });
  $("#soldUnitsTableBody").html(html);
}

function renderRepossessedUnitsTable(data) {
  let html = "";
  if (!data || data.length === 0) {
    $("#repossessedUnitsTableBody").html(
      '<tr><td colspan="8" class="text-center py-4">No repossessed units found.</td></tr>'
    );
    return;
  }
  data.forEach((item) => {
    html += `<tr>
            <td>${escapeHtml(item.model)}</td>
            <td><code>${escapeHtml(item.engine_number)}</code></td>
            <td>${escapeHtml(item.current_branch)}</td>
            <td>${getStatusBadge(item.status)}</td>
            <td class="text-end">
                <button class="btn btn-sm btn-warning revert-btn" data-id="${
                  item.id
                }" data-type="repo" title="Revert Repossession"><i class="bi bi-arrow-counterclockwise"></i></button>
                <button class="btn btn-sm btn-info" onclick="loadMotorcycleForEdit(${
                  item.id
                })" title="Edit Details"><i class="bi bi-pencil-square"></i></button>
            </td>
        </tr>`;
  });
  $("#repossessedUnitsTableBody").html(html);
}

function renderScrappedUnitsTable(data) {
  let html = "";
  if (!data || data.length === 0) {
    $("#scrappedUnitsTableBody").html(
      '<tr><td colspan="7" class="text-center py-4">No scrapped units found.</td></tr>'
    );
    return;
  }
  data.forEach((item) => {
    html += `<tr>
            <td>${formatDate(item.scrap_date)}</td>
            <td>${escapeHtml(item.model)}</td>
            <td><code>${escapeHtml(item.engine_number)}</code></td>
            <td>${escapeHtml(item.current_branch)}</td>
            <td class="text-end">${formatCurrency(item.inventory_cost)}</td>
            <td>${escapeHtml(item.scrap_reason)}</td>
            <td class="text-end">
                <button class="btn btn-sm btn-warning revert-btn" data-id="${
                  item.id
                }" data-type="scrapped" title="Revert Scrap"><i class="bi bi-arrow-counterclockwise"></i></button>
            </td>
        </tr>`;
  });
  $("#scrappedUnitsTableBody").html(html);
}

function renderRedeemedUnitsTable(data) {
  let html = "";
  if (!data || data.length === 0) {
    $("#redeemedUnitsTableBody").html(
      '<tr><td colspan="8" class="text-center py-4">No redeemed units found.</td></tr>'
    );
    return;
  }
  data.forEach((item) => {
    html += `<tr>
            <td>${formatDate(item.redeem_date)}</td>
            <td>${escapeHtml(item.redeemed_by_customer)}</td>
            <td>${escapeHtml(item.model)}</td>
            <td><code>${escapeHtml(item.engine_number)}</code></td>
            <td>${escapeHtml(item.current_branch)}</td>
            <td class="text-end">${formatCurrency(item.amount_paid)}</td>
            <td class="text-end">
                <button class="btn btn-sm btn-warning revert-btn" data-id="${
                  item.id
                }" data-type="redeemed" title="Revert Redemption"><i class="bi bi-arrow-counterclockwise"></i></button>
            </td>
        </tr>`;
  });
  $("#redeemedUnitsTableBody").html(html);
}



/**
 * Renders a smart, truncated pagination control.
 * Shows only a limited number of pages around the current page.
 * @param {string} selector - The jQuery selector for the <ul> pagination element.
 * @param {number} currentPage - The current active page number.
 * @param {number} totalPages - The total number of pages available.
 * @param {string} callbackFunctionName - The name of the JS function to call on page click (e.g., 'loadSoldUnits').
 */
function renderGenericPagination(
  selector,
  currentPage,
  totalPages,
  callbackFunctionName
) {
  const paginationEl = $(selector);
  paginationEl.empty();
  if (totalPages <= 1) {
    return; 
  }

  const maxVisiblePages = 7; 
  const sidePages = Math.floor((maxVisiblePages - 1) / 2);

  const createPageItem = (page, text, isDisabled = false, isActive = false) => {
    const disabledClass = isDisabled ? "disabled" : "";
    const activeClass = isActive ? "active" : "";
    const searchInput = $(selector)
      .closest(".tab-pane")
      .find("input[type=text]");
    const query =
      searchInput.length > 0 ? `'${escapeHtml(searchInput.val())}'` : "''";

    
    const clickHandler = isDisabled
      ? ""
      : `onclick="${callbackFunctionName}(${page}, ${query})"`;

    return `<li class="page-item ${disabledClass} ${activeClass}"><a class="page-link" href="#" ${clickHandler}>${text}</a></li>`;
  };

  
  paginationEl.append(
    createPageItem(currentPage - 1, "&laquo;", currentPage === 1)
  );

  let startPage, endPage;

  if (totalPages <= maxVisiblePages) {
    
    startPage = 1;
    endPage = totalPages;
  } else {
    
    if (currentPage <= sidePages + 1) {
      startPage = 1;
      endPage = maxVisiblePages - 1;
    } else if (currentPage + sidePages >= totalPages) {
      startPage = totalPages - (maxVisiblePages - 2);
      endPage = totalPages;
    } else {
      startPage = currentPage - sidePages;
      endPage = currentPage + sidePages;
    }
  }

  
  if (startPage > 1) {
    paginationEl.append(createPageItem(1, "1"));
    if (startPage > 2) {
      paginationEl.append(
        '<li class="page-item disabled"><span class="page-link">...</span></li>'
      );
    }
  }

  
  for (let i = startPage; i <= endPage; i++) {
    paginationEl.append(createPageItem(i, i, false, i === currentPage));
  }

  
  if (endPage < totalPages) {
    if (endPage < totalPages - 1) {
      paginationEl.append(
        '<li class="page-item disabled"><span class="page-link">...</span></li>'
      );
    }
    paginationEl.append(createPageItem(totalPages, totalPages));
  }

  
  paginationEl.append(
    createPageItem(currentPage + 1, "&raquo;", currentPage === totalPages)
  );
}
function revertTransaction() {
  const revertId = $("#revertId").val();
  const revertType = $("#revertType").val();

  $.ajax({
    url: "../api/inventory_management.php",
    method: "POST",
    data: { action: "revert_transaction", id: revertId, type: revertType },
    dataType: "json",
    success: function (response) {
      $("#revertConfirmationModal").modal("hide");
      if (response.success) {
        
        showSuccessModal(response.message); 
        setTimeout(() => {
          location.reload();
        }, 1500);
      } else {
        showErrorModal(response.message);
      }
    },
  });
}
function getStatusBadge(status) {
  if (!status) return "";
  switch (status.toLowerCase()) {
    case "available":
      return '<span class="badge bg-success">Available</span>';
    case "sold":
      return '<span class="badge bg-danger">Sold</span>';
    case "transferred":
      return '<span class="badge bg-warning text-dark">Transferred</span>';
    case "scrapped":
      return '<span class="badge bg-dark">Scrapped</span>';
    default:
      return `<span class="badge bg-secondary">${escapeHtml(status)}</span>`;
  }
}





/**
 * Loads the system-wide activity log with pagination and search.
 * @param {number} [page=1] - The page number to load.
 * @param {string} [query=''] - The search term.
 */
function loadActivityLog(page = 1, query = "") {
  loadGenericLogData(
    "get_activity_log",
    "#activityLogTableBody",
    "#activityLogPagination",
    renderActivityLogTable,
    "loadActivityLog",
    page,
    query
  );
}

/**
 * Renders the HTML for the activity log table.
 * @param {Array<object>} data - The log data from the API.
 */
function renderActivityLogTable(data) {
  let html = "";
  if (!data || data.length === 0) {
    $("#activityLogTableBody").html(
      '<tr><td colspan="5" class="text-center py-4">No activity found.</td></tr>'
    );
    return;
  }
  data.forEach((item) => {
    html += `<tr>
            <td>${formatDateTime(item.action_timestamp)}</td>
            <td>${escapeHtml(item.username)}</td>
            <td>
                <span class="badge bg-secondary">${escapeHtml(
                  item.action_type
                )}</span>
                <small class="d-block text-muted">${escapeHtml(
                  item.table_name
                )}</small>
            </td>
            <td class="text-center"><code>${escapeHtml(
              item.record_id
            )}</code></td>
            <td class="small">${escapeHtml(item.action_details)}</td>
        </tr>`;
  });
  $("#activityLogTableBody").html(html);
}




/**
 * Loads transfers for a specific status (in-transit, completed, rejected) into the UI.
 * This is the core function for the Global Transfer History tab.
 * @param {string} status - The status of transfers to load.
 * @param {string} tableBodyId - The ID of the table body element to populate.
 * @param {string} paginationId - The ID of the pagination UL element.
 * @param {string} countId - The ID of the badge element to show the total count.
 * @param {string} [searchTerm=''] - The search term to filter results.
 * @param {number} [page=1] - The page number to retrieve.
 */
function loadTransfers(
  status,
  tableBodyId,
  paginationId,
  countId,
  searchTerm = "",
  page = 1
) {
  const loadingHtml = `<tr><td colspan="2" class="text-center py-4"><div class="spinner-border spinner-border-sm"></div></td></tr>`;
  $(tableBodyId).html(loadingHtml);

  $.ajax({
    url: "../api/inventory_management.php",
    method: "GET",
    data: {
      action: "get_transfers_by_status",
      status: status,
      query: searchTerm,
      page: page,
      limit: 10, 
    },
    dataType: "json",
    success: function (response) {
      $(countId).text(response.pagination.totalRecords || 0);
      let html = "";
      if (response.success && response.data.length > 0) {
        response.data.forEach((item) => {
          
          const modelsPreview = item.models
            ? item.models.split(",").slice(0, 2).join(", ")
            : "N/A";
          const modelsEllipsis =
            item.models && item.models.split(",").length > 2 ? "..." : "";

          html += `
                        <tr>
                            <td class="w-100">
                                <div class="fw-bold"><i class="bi bi-receipt"></i> ${escapeHtml(
                                  item.transfer_invoice_number
                                )}</div>
                                <div class="small text-muted"><i class="bi bi-arrow-right-short"></i> From: <strong>${escapeHtml(
                                  item.from_branch
                                )}</strong></div>
                                <div class="small text-muted"><i class="bi bi-arrow-left-short"></i> To: <strong>${escapeHtml(
                                  item.to_branch
                                )}</strong></div>
                                <div class="small text-muted"><i class="bi bi-calendar-event"></i> ${formatDate(
                                  item.transfer_date
                                )}</div>
                                <div class="small text-muted" title="${escapeHtml(
                                  item.models
                                )}">
                                    <i class="bi bi-stack"></i> ${
                                      item.total_units
                                    } units (${escapeHtml(
            modelsPreview
          )}${modelsEllipsis})
                                </div>
                            </td>
                            <td class="align-middle">
                                <div class="btn-group-vertical btn-group-sm">
                                    <button class="btn btn-outline-primary" title="View & Print" onclick="loadTransferReceipt(${
                                      item.header_id
                                    })">
                                        <i class="bi bi-printer"></i>
                                    </button>
                                    <button class="btn btn-outline-secondary" title="View/Edit Units" 
                                            onclick="openManageTransferModalById('${
                                              item.transfer_invoice_number
                                            }')">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" title="Delete Transfer" data-bs-toggle="modal" data-bs-target="#deleteTransferConfirmationModal" data-transfer-id="${
                                      item.header_id
                                    }">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
        });
      } else {
        html = `<tr><td colspan="2" class="text-center text-muted py-4 small">No ${status} transfers found.</td></tr>`;
      }
      $(tableBodyId).html(html);
      renderTransferPagination(
        paginationId,
        page,
        response.pagination.totalPages
      );
    },
    error: function () {
      $(countId).text("0");
      $(tableBodyId).html(
        `<tr><td colspan="2" class="text-center text-danger py-4 small">Error loading data.</td></tr>`
      );
    },
  });
}
function openManageTransferModalById(invoiceNumber) {
  if (!invoiceNumber) {
    showErrorModal("Cannot edit transfer without an invoice number.");
    return;
  }

  
  $("#manageTransferLoading").show();
  $("#manageTransferContent").hide();

  
  populateBranchesDropdowns(
    ["#manageTransferFromBranch", "#manageTransferToBranch"],
    function () {
      
      $.ajax({
        url: "../api/inventory_management.php",
        method: "GET",
        data: {
          action: "get_transfer_details_by_invoice",
          transfer_invoice_number: invoiceNumber, 
        },
        dataType: "json",
        success: function (response) {
          if (response.success) {
            const header = response.header;
            const motorcycles = response.motorcycles || [];

            
            managingTransfer = {
              originalInvoiceNumber: header.transfer_invoice_number,
              fromBranch: header.from_branch,
              initialItems: motorcycles,
              itemsToAdd: [],
              itemsToRemove: [],
            };

            
            $("#manageTransferModalLabel").text(
              `Manage Transfer: ${managingTransfer.originalInvoiceNumber}`
            );
            $("#manageTransferFrom").text(header.from_branch);

            $("#manageTransferDate").val(formatDate(header.transfer_date));
            $("#manageDateReceived").val(formatDate(header.date_received));
            $("#manageTransferInvoice").val(header.transfer_invoice_number);
            $("#manageDateReceived").val(header.date_received);
            $("#manageTransferFromBranch").val(header.from_branch);
            $("#manageTransferToBranch").val(header.to_branch);
            $("#manageTransferNotes").val(header.notes || "");

            
            renderManagingTransferLists();

            
            $("#manageTransferModal").modal("show");

            
            $("#manageTransferLoading").hide();
            $("#manageTransferContent").show();
          } else {
            showErrorModal(response.message || "Error loading transfer data.");
          }
        },
        error: function (xhr, status, error) {
          showErrorModal("Error fetching transfer details: " + error);
        },
      });
    }
  );
}

document.addEventListener("DOMContentLoaded", function () {
  const transferStatus = "completed";

  if (transferStatus === "completed") {
    document
      .getElementById("manageDateReceivedContainer")
      .classList.remove("d-none");
  }
});

function renderManagingTransferLists() {
  const initialList = $("#managingTransferInitialList");
  initialList.empty();
  let totalInitial = 0;
  let itemsAddedCount = managingTransfer.itemsToAdd.length;
  let itemsRemovedCount = managingTransfer.itemsToRemove.length;

  
  managingTransfer.initialItems.forEach((item) => {
    if (!managingTransfer.itemsToRemove.includes(item.id)) {
      totalInitial++;
      initialList.append(`
               <div class="transfer-item d-flex justify-content-between align-items-center">
        <span>${escapeHtml(item.brand)} ${escapeHtml(item.model)} 
            <small class="text-muted">(${escapeHtml(
              item.engine_number
            )})</small>
        </span>
        <button class="btn btn-sm btn-outline-danger" onclick="removeItemFromTransfer(${
          item.id
        })">
            <i class="bi bi-trash"></i>
        </button>
    </div>
            `);
    }
  });

  
  managingTransfer.itemsToRemove.forEach((itemId) => {
    const item = managingTransfer.initialItems.find((i) => i.id === itemId);
    if (item) {
      initialList.append(`
                <div class="transfer-item to-be-removed d-flex justify-content-between align-items-center">
                    <span>${escapeHtml(item.brand)} ${escapeHtml(
        item.model
      )} <small>(${escapeHtml(item.engine_number)})</small></span>
                    <button class="btn btn-sm btn-outline-secondary" onclick="undoRemoveFromTransfer(${
                      item.id
                    })"><i class="bi bi-arrow-counterclockwise"></i></button>
                </div>
            `);
    }
  });

  
  managingTransfer.itemsToAdd.forEach((item) => {
    initialList.append(`
            <div class="transfer-item to-be-added d-flex justify-content-between align-items-center">
                <span>${escapeHtml(item.brand)} ${escapeHtml(
      item.model
    )} <small class="text-muted">(${escapeHtml(
      item.engine_number
    )})</small></span>
                <button class="btn btn-sm btn-outline-danger" onclick="undoAddToTransfer(${
                  item.id
                })"><i class="bi bi-x-lg"></i></button>
            </div>
        `);
  });

  if (totalInitial === 0 && itemsAddedCount === 0) {
    initialList.html(
      '<p class="text-muted text-center small p-3">No items in this transfer.</p>'
    );
  }

  
  $("#manageTransferTotal").text(totalInitial + itemsAddedCount);
  $("#manageTransferAdded").text(itemsAddedCount);
  $("#manageTransferRemoved").text(itemsRemovedCount);
}


function removeItemFromTransfer(motorcycleId) {
  if (!managingTransfer.itemsToRemove.includes(motorcycleId)) {
    managingTransfer.itemsToRemove.push(motorcycleId);
  }
  renderManagingTransferLists();
}

function undoRemoveFromTransfer(motorcycleId) {
  managingTransfer.itemsToRemove = managingTransfer.itemsToRemove.filter(
    (id) => id !== motorcycleId
  );
  renderManagingTransferLists();
}

function addItemToTransfer(id, brand, model, engine, cost) {
  const isAlreadyInitial = managingTransfer.initialItems.some(
    (item) => item.id === id
  );
  const isAlreadyAdded = managingTransfer.itemsToAdd.some(
    (item) => item.id === id
  );

  if (isAlreadyInitial || isAlreadyAdded) {
    showErrorModal("This motorcycle is already in the transfer list.");
    return;
  }

  managingTransfer.itemsToAdd.push({
    id,
    brand,
    model,
    engine_number: engine,
    inventory_cost: cost,
  });
  renderManagingTransferLists();
  $("#manageTransferSearch").val("").focus();
  $("#manageTransferSearchResults").empty();
}

function undoAddToTransfer(motorcycleId) {
  managingTransfer.itemsToAdd = managingTransfer.itemsToAdd.filter(
    (item) => item.id !== motorcycleId
  );
  renderManagingTransferLists();
}

/**
 * Searches for available motorcycles from the correct 'from' branch to add to a transfer.
 */
function searchAvailableForTransfer() {
  const query = $("#manageTransferSearch").val();
  const resultsContainer = $("#manageTransferSearchResults");
  if (query.length < 2) {
    resultsContainer.empty();
    return;
  }

  $.ajax({
    url: "../api/inventory_management.php",
    method: "GET",
    data: {
      action: "search_inventory_by_engine",
      branch: managingTransfer.fromBranch, 
      query: query,
    },
    dataType: "json",
    success: function (response) {
      resultsContainer.empty();
      if (response.success && response.data.length > 0) {
        response.data.forEach((item) => {
          resultsContainer.append(`
                        <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${escapeHtml(item.model)}</strong>
                                <small class="text-muted d-block">${escapeHtml(
                                  item.engine_number
                                )}</small>
                            </div>
                            <button class="btn btn-sm btn-success" onclick="addItemToTransfer(${
                              item.id
                            }, '${escapeHtml(item.brand)}', '${escapeHtml(
            item.model
          )}', '${escapeHtml(item.engine_number)}', ${item.inventory_cost})">
                                <i class="bi bi-plus-lg"></i> Add
                            </button>
                        </div>
                    `);
        });
      } else {
        resultsContainer.html(
          '<div class="list-group-item text-muted">No available units found.</div>'
        );
      }
    },
  });
}

/**
 * Submits all updated transfer details (including date, branches, etc.) to the backend.
 */
function submitTransferUpdate() {
  
  const dataToSend = {
    action: "update_transfer_group", 
    original_invoice_number: managingTransfer.originalInvoiceNumber, 
    transfer_invoice_number: $("#manageTransferInvoice").val(), 
    from_branch: $("#manageTransferFromBranch").val(),
    to_branch: $("#manageTransferToBranch").val(),
    transfer_date: $("#manageTransferDate").val(),
    date_received: $("#manageDateReceived").val(),
    notes: $("#manageTransferNotes").val(),
    motorcycles_to_add: managingTransfer.itemsToAdd,
    motorcycles_to_remove: managingTransfer.itemsToRemove,
  };

  
  if (
    !dataToSend.transfer_invoice_number ||
    !dataToSend.to_branch ||
    !dataToSend.transfer_date
  ) {
    showErrorModal(
      "Transfer Date, Invoice Number, and To Branch cannot be empty."
    );
    return;
  }

  $("#saveTransferChangesBtn")
    .prop("disabled", true)
    .html('<span class="spinner-border spinner-border-sm"></span> Saving...');

  $.ajax({
    url: "../api/inventory_management.php",
    method: "POST",
    data: dataToSend,
    dataType: "json",
    success: function (response) {
      if (response.success) {
        showSuccessModal(response.message);
        $("#manageTransferModal").modal("hide");
        
        $("#globalTransferSearchBtn").click();
      } else {
        showErrorModal(response.message || "Failed to update transfer.");
      }
    },
    error: function () {
      showErrorModal("An unexpected error occurred. Please try again.");
    },
    complete: function () {
      $("#saveTransferChangesBtn").prop("disabled", false).html("Save Changes");
    },
  });
}
/**
 * Renders pagination controls for the transfer history cards.
 */
function renderTransferPagination(paginationId, currentPage, totalPages) {
  const paginationEl = $(paginationId);
  paginationEl.empty();
  if (totalPages <= 1) return;

  const prevDisabled = currentPage === 1 ? "disabled" : "";
  paginationEl.append(
    `<li class="page-item ${prevDisabled}"><a class="page-link" href="#" data-page="${
      currentPage - 1
    }">&laquo;</a></li>`
  );

  paginationEl.append(
    `<li class="page-item disabled"><span class="page-link">${currentPage} of ${totalPages}</span></li>`
  );

  const nextDisabled = currentPage === totalPages ? "disabled" : "";
  paginationEl.append(
    `<li class="page-item ${nextDisabled}"><a class="page-link" href="#" data-page="${
      currentPage + 1
    }">&raquo;</a></li>`
  );
}

function openManageTransferModalById(invoiceNumber) {
  if (!invoiceNumber) {
    showErrorModal("Cannot edit transfer without an invoice number.");
    return;
  }

  
  $("#manageTransferLoading").show();
  $("#manageTransferContent").hide();

  
  populateBranchesDropdowns(
    ["#manageTransferFromBranch", "#manageTransferToBranch"],
    function () {
      
      $.ajax({
        url: "../api/inventory_management.php",
        method: "GET",
        data: {
          action: "get_transfer_details_by_invoice",
          transfer_invoice_number: invoiceNumber,
        },
        dataType: "json",
        success: function (response) {
          if (response.success) {
            const header = response.header;
            const motorcycles = response.motorcycles || [];

            managingTransfer = {
              originalInvoiceNumber: header.transfer_invoice_number,
              fromBranch: header.from_branch,
              initialItems: motorcycles,
              itemsToAdd: [],
              itemsToRemove: [],
            };

            
            $("#manageTransferModalLabel")
              .text(`Manage Transfer: ${header.transfer_invoice_number}`)
              .css("color", "white");
            $("#manageTransferDate").val(formatDate(header.transfer_date));
            $("#manageDateReceived").val(formatDate(header.date_received));
            $("#manageTransferInvoice").val(header.transfer_invoice_number);
            $("#manageTransferNotes").val(header.notes || "");

            
            $("#manageTransferFromBranch")
              .val(header.from_branch)
              .trigger("change");
            $("#manageTransferToBranch")
              .val(header.to_branch)
              .trigger("change");

            
            renderManagingTransferLists();

            
            $("#manageTransferModal").modal("show");

            
            $("#manageTransferLoading").hide();
            $("#manageTransferContent").show();
          } else {
            showErrorModal(response.message || "Error loading transfer data.");
          }
        },
        error: function (xhr, status, error) {
          showErrorModal("Error fetching transfer details: " + error);
        },
      });
    }
  );
}

/**
 * Handles the AJAX call to delete a transfer record after confirmation.
 * This function is triggered by the "Delete" button.
 */
function deleteTransfer(transferId) {
  $.ajax({
    url: "../api/inventory_management.php",
    method: "POST",
    data: {
      action: "delete_transfer",
      transfer_header_id: transferId,
    },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        $("#deleteTransferConfirmationModal").modal("hide");
        showSuccessModal("Transfer record deleted successfully.");
        
        $("#globalTransferSearchBtn").click();
      } else {
        showErrorModal(
          response.message || "Failed to delete the transfer record."
        );
      }
    },
    error: function () {
      showErrorModal("An error occurred while deleting the transfer.");
    },
  });
}





function updateTransferSummary(transfers) {
  if (!transfers || !Array.isArray(transfers)) {
    transfers = [];
  }

  const totalUnits = transfers.length;
  const fromBranches = [...new Set(transfers.map((t) => t.from_branch))].join(
    ", "
  );

  $("#summaryTotalUnits").text(totalUnits);
  $("#summaryFromBranches").text(fromBranches);
}

function updateMotorcycleCost(motorcycleId, newCost) {
  const motorcycle = selectedMotorcycles.find((m) => m.id === motorcycleId);
  if (motorcycle) {
    motorcycle.inventory_cost = parseFloat(newCost) || 0;
    updateTransferSummary();
  }
}

function saveCost(motorcycleId) {
  const costInput = document.getElementById(`inventory-cost-${motorcycleId}`);
  const newCost = parseFloat(costInput.value);

  if (!isNaN(newCost) && newCost >= 0) {
    const motorcycle = selectedMotorcycles.find((m) => m.id === motorcycleId);
    if (motorcycle) {
      motorcycle.inventory_cost = newCost;
      showSuccessModal("Cost updated successfully!");
      updateTransferSummary();
    }
  } else {
    showErrorModal("Please enter a valid cost value.");
  }
}

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
          <td>${transfer.transfer_invoice_number || "N/A"}</td>
          <td>${statusBadge}</td>
        </tr>
      `);
    });

    updateTransferSummary(transfers);
  }

  updateTransferSelection();

  if (!hasShownIncomingTransfers) {
    $("#incomingTransfersModal").modal("show");
    hasShownIncomingTransfers = true;
  }
}

function updateTransferSelection() {
  const selectedCount = selectedTransferIds.length;

  $("#selectedTransfersCount").text(`${selectedCount} selected`);

  $("#acceptSelectedBtn, #rejectSelectedBtn").prop(
    "disabled",
    selectedCount === 0
  );

  $(".transfer-row").removeClass("selected");
  selectedTransferIds.forEach((id) => {
    $(`.transfer-row[data-transfer-id='${id}']`).addClass("selected");
  });
  if (selectedCount > 0) {
    updateSelectedTransfersSummary();
    $("#transferSummary").show();
  } else {
    $("#transferSummary").hide();
  }
}

function updateTransferSummary(transfers) {
  if (transfers && Array.isArray(transfers)) {
    const totalUnits = transfers.length;
    const fromBranches =
      transfers.length > 0
        ? [...new Set(transfers.map((t) => t.from_branch))].join(", ")
        : "-";

    $("#summaryTotalUnits").text(totalUnits);
    $("#summaryFromBranches").text(fromBranches);
  } else {
    const selectedCount = selectedMotorcycles.length;
    const totalInventoryCost = selectedMotorcycles.reduce(
      (sum, motorcycle) => sum + (parseFloat(motorcycle.inventory_cost) || 0),
      0
    );

    $("#selectedCount").text(selectedCount);
    $("#totalInventoryCostValue").text(formatCurrency(totalInventoryCost));

    const progressPercentage = Math.min((selectedCount / 10) * 100, 100);
    $("#selectionProgress").css("width", progressPercentage + "%");

    if ($("#summaryTotalUnits").length) {
      $("#summaryTotalUnits").text(selectedCount);
    }

    const uniqueBranches = [
      ...new Set(selectedMotorcycles.map((m) => m.current_branch)),
    ];
    if ($("#summaryFromBranches").length) {
      $("#summaryFromBranches").text(uniqueBranches.join(", ") || "-");
    }
  }
}

function updateSelectedTransfersSummary() {
  const selectedCount = selectedTransferIds.length;
  const selectedBranches = [];

  selectedTransferIds.forEach((id) => {
    const row = $(`.transfer-row[data-transfer-id='${id}']`);
    const branch = row.find("td:nth-child(7) .badge").text();
    if (branch && !selectedBranches.includes(branch)) {
      selectedBranches.push(branch);
    }
  });

  $("#summarySelectedCount").text(selectedCount);
  $("#summaryFromBranches").text(selectedBranches.join(", ") || "-");
}

function acceptSelectedTransfers() {
  if (selectedTransferIds.length === 0) {
    showErrorModal("No transfers selected");
    return;
  }

  $("#acceptSelectedBtn")
    .prop("disabled", true)
    .html('<i class="spinner-border spinner-border-sm me-2"></i>Processing...');

  $.ajax({
    url: "../api/inventory_management.php",
    method: "POST",
    data: {
      action: "accept_transfers",
      transfer_ids: selectedTransferIds.join(","),
      current_branch: currentBranch,
    },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        showSuccessModal(
          response.message || "Selected transfers accepted successfully!"
        );
        $("#incomingTransfersModal").modal("hide");

        selectedTransferIds = [];
        hasShownIncomingTransfers = false;

        setTimeout(function () {
          window.location.reload();
        }, 2000);
      } else {
        showErrorModal(response.message || "Error accepting transfers");
        $("#acceptSelectedBtn")
          .prop("disabled", false)
          .html('<i class="bi bi-check-circle me-1"></i>Accept Selected');
      }
    },
    error: function (xhr, status, error) {
      console.error("AJAX Error:", xhr.responseText);
      showErrorModal("Error accepting transfers: " + error);
      $("#acceptSelectedBtn")
        .prop("disabled", false)
        .html('<i class="bi bi-check-circle me-1"></i>Accept Selected');
    },
  });
}

function rejectSelectedTransfers() {
  if (selectedTransferIds.length === 0) {
    showErrorModal("No transfers selected");
    return;
  }

  $("#rejectSelectedBtn")
    .prop("disabled", true)
    .html('<i class="spinner-border spinner-border-sm me-2"></i>Processing...');

  $.ajax({
    url: "../api/inventory_management.php",
    method: "POST",
    data: {
      action: "reject_transfers",
      transfer_ids: selectedTransferIds.join(","),
      current_branch: currentBranch,
    },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        showSuccessModal(
          response.message || "Selected transfers rejected successfully!"
        );

        selectedTransferIds.forEach((id) => {
          $(`.transfer-row[data-transfer-id='${id}']`).fadeOut(
            300,
            function () {
              $(this).remove();
              if ($("#incomingTransfersBody tr:visible").length === 0) {
                $("#incomingTransfersBody").html(`
                <tr>
                  <td colspan="9" class="text-center py-4 text-muted">No in-transit transfers remaining</td>
                </tr>
              `);
                $("#transferSummary").hide();
              }
            }
          );
        });

        selectedTransferIds = [];
        updateTransferSelection();

        $("#rejectSelectedBtn")
          .prop("disabled", false)
          .html('<i class="bi bi-x-circle me-1"></i>Reject Selected');
      } else {
        showErrorModal(response.message || "Error rejecting transfers");
        $("#rejectSelectedBtn")
          .prop("disabled", false)
          .html('<i class="bi bi-x-circle me-1"></i>Reject Selected');
      }
    },
    error: function (xhr, status, error) {
      console.error("AJAX Error:", xhr.responseText);
      showErrorModal("Error rejecting transfers: " + error);
      $("#rejectSelectedBtn")
        .prop("disabled", false)
        .html('<i class="bi bi-x-circle me-1"></i>Reject Selected');
    },
  });
}






function searchInvoiceNumber() {
  const invoiceNumber = $("#invoiceNumberSearch").val().trim();

  if (!invoiceNumber) {
    showErrorModal("Please enter an invoice number to search");
    return;
  }

  $("#invoiceSearchResults").html(`
        <div class="text-center py-4">
            <div class="spinner-border spinner-border-sm text-black" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 small text-muted">Searching for invoice...</p>
        </div>
    `);
  $("#invoiceSearchResultsContainer").show();

  $.ajax({
    url: "../api/inventory_management.php",
    method: "GET",
    data: {
      action: "search_invoice_number",
      invoice_number: invoiceNumber,
    },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        displayInvoiceSearchResults(response.data);
      } else {
        showErrorModal(response.message || "Error searching invoice");
        $("#invoiceSearchResultsContainer").hide();
      }
    },
    error: function (xhr, status, error) {
      showErrorModal("Error searching invoice: " + error);
      $("#invoiceSearchResultsContainer").hide();
    },
  });
}

function displayInvoiceSearchResults(data) {
  const $resultsContainer = $("#invoiceSearchResults");

  if (data.length === 0) {
    $resultsContainer.html(`
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                No invoices found with that number.
            </div>
        `);
    return;
  }

  let html = '<div class="list-group">';

  data.forEach((invoice) => {
    const dateDelivered = formatDate(invoice.date_delivered);
    const modelsList =
      invoice.models && invoice.models.length > 0
        ? invoice.models.slice(0, 3).join(", ") +
          (invoice.models.length > 3 ? "..." : "")
        : "No models";

    html += `
            <div class="list-group-item list-group-item-action">
                <div class="d-flex w-100 justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <h6 class="mb-1 text-black">${escapeHtml(
                          invoice.invoice_number
                        )}</h6>
                        <p class="mb-1 small">
                            <strong>Date Delivered:</strong> ${dateDelivered}<br>
                            <strong>Branch:</strong> ${escapeHtml(
                              invoice.branch
                            )}<br>
                            <strong>Models:</strong> ${escapeHtml(
                              modelsList
                            )}<br>
                            <strong>Motorcycles:</strong> ${
                              invoice.motorcycle_count || 0
                            }
                        </p>
                    </div>
                    <button class="btn btn-sm btn-outline-primary view-invoice-details ms-2"
                            data-invoice-id="${invoice.id}"
                            data-invoice-number="${escapeHtml(
                              invoice.invoice_number
                            )}">
                        <i class="bi bi-eye"></i> Details
                    </button>
                </div>
            </div>
        `;
  });

  html += "</div>";
  $resultsContainer.html(html);

  $(".view-invoice-details")
    .off("click")
    .on("click", function () {
      const invoiceId = $(this).data("invoice-id");
      const invoiceNumber = $(this).data("invoice-number");
      viewInvoiceDetails(invoiceId, invoiceNumber);
    });
}

function viewInvoiceDetails(invoiceId, invoiceNumber) {
  $("#invoiceSearchResults").html(`
        <div class="text-center py-4">
            <div class="spinner-border spinner-border-sm text-black" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 small text-muted">Loading invoice details...</p>
        </div>
    `);

  $.ajax({
    url: "../api/inventory_management.php",
    method: "GET",
    data: {
      action: "get_invoice_details",
      invoice_id: invoiceId,
    },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        displayInvoiceDetails(response.data, invoiceNumber);
      } else {
        showErrorModal(response.message || "Error loading invoice details");
        searchInvoiceNumber();
      }
    },
    error: function (xhr, status, error) {
      showErrorModal("Error loading invoice details: " + error);
      searchInvoiceNumber();
    },
  });
}

function displayInvoiceDetails(invoice, invoiceNumber) {
  const dateDelivered = formatDate(invoice.date_delivered);
  const notes = invoice.notes || "No notes provided";

  let html = `
        <div class="card">
            <div class="card-header bg-light">
                <h6 class="mb-0">Invoice Details: ${escapeHtml(
                  invoiceNumber
                )}</h6>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p><strong>Invoice Number:</strong> ${escapeHtml(
                          invoice.invoice_number
                        )}</p>
                        <p><strong>Date Delivered:</strong> ${dateDelivered}</p>
                        <p><strong>Notes:</strong> ${escapeHtml(notes)}</p>
                    </div>
                </div>
                
                <h6 class="border-bottom pb-2">Motorcycles in this Invoice</h6>
    `;

  if (invoice.motorcycles && invoice.motorcycles.length > 0) {
    html += `
            <div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>Brand</th>
                            <th>Model</th>
                            <th>Color</th>
                            <th>Engine Number</th>
                            <th>Frame Number</th>
                            <th>Status</th>
                            <th>Current Branch</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

    invoice.motorcycles.forEach((motorcycle) => {
      const statusClass = getStatusBadgeClass(motorcycle.status);
      html += `
                <tr>
                    <td>${escapeHtml(motorcycle.brand)}</td>
                    <td>${escapeHtml(motorcycle.model)}</td>
                    <td>${escapeHtml(motorcycle.color)}</td>
                    <td><code>${escapeHtml(
                      motorcycle.engine_number
                    )}</code></td>
                    <td><code>${escapeHtml(motorcycle.frame_number)}</code></td>
                    <td><span class="badge ${statusClass}">${
        motorcycle.status
      }</span></td>
                    <td>${escapeHtml(motorcycle.current_branch)}</td>
                </tr>
            `;
    });

    html += `
                    </tbody>
                </table>
            </div>
        `;
  } else {
    html += `
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                No motorcycles found in this invoice.
            </div>
        `;
  }

  html += `
             <div class="mt-3 d-flex justify-content-between">
        <button class="btn btn-primary text-white btn-sm" id="printInvoiceBtn">
            <i class="bi bi-printer me-1"></i> Print Invoice
        </button>
        <button class="btn btn-secondary btn-sm" onclick="searchInvoiceNumber()">
            <i class="bi bi-arrow-left me-1"></i> Back to Search Results
        </button>
    </div>
    `;

  $("#invoiceSearchResults").html(html);
  $("#printInvoiceBtn")
    .off("click")
    .on("click", function () {
      printInvoice(invoice, invoiceNumber);
    });
}

function printInvoice(invoice, invoiceNumber) {
  const dateDelivered = formatDate(invoice.date_delivered);
  const notes = invoice.notes || "No notes provided";

  const html = `
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8" />
        <title>Invoice - ${escapeHtml(invoiceNumber)}</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 10px 15px;
                color: #333;
                font-size: 12px;
                line-height: 1.3;
            }
            .header {
                text-align: center;
                border-bottom: 2px solid #000f71;
                padding-bottom: 8px;
                margin-bottom: 15px;
            }
            .header h4 {
                margin: 0;
                color: #000f71;
                font-weight: 700;
                letter-spacing: 1px;
                font-size: 16px;
            }
            .header h5 {
                margin: 4px 0 0 0;
                color: #495057;
                font-weight: 600;
                font-size: 13px;
            }
            .info-section {
                display: flex;
                justify-content: space-between;
                margin-bottom: 15px;
                flex-wrap: wrap;
            }
            .info-card {
                border: 1px solid #e9ecef;
                border-radius: 6px;
                padding: 10px 15px;
                width: 48%;
                box-sizing: border-box;
                margin-bottom: 10px;
            }
            .info-card h6 {
                margin: 0 0 8px 0;
                font-weight: 600;
                color: #000f71;
                font-size: 13px;
                border-bottom: 1px solid #dee2e6;
                padding-bottom: 4px;
            }
            .info-card p {
                margin: 2px 0;
                font-size: 12px;
                color: #495057;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 15px;
                font-size: 11px;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 6px 8px;
                text-align: left;
                vertical-align: middle;
            }
            th {
                background-color: #f1f1f1;
                font-weight: 600;
                color: #333;
            }
            code {
                font-family: monospace;
                font-size: 11px;
            }
            .badge {
                display: inline-block;
                padding: 0.25em 0.5em;
                font-size: 75%;
                font-weight: 700;
                color: #fff;
                border-radius: 0.25rem;
                white-space: nowrap;
            }
            .bg-success { background-color: #198754; }
            .bg-danger { background-color: #dc3545; }
            .bg-warning { background-color: #ffc107; color: #212529; }
            .bg-secondary { background-color: #6c757d; }
            .footer {
                text-align: center;
                font-size: 10px;
                color: #666;
                border-top: 1px solid #ddd;
                padding-top: 8px;
                margin-top: 10px;
            }
            @media print {
                body {
                    margin: 0.5in;
                    font-size: 11px;
                }
                .info-section {
                    flex-wrap: nowrap;
                }
                .info-card {
                    width: 48%;
                    margin-bottom: 0;
                }
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h4>SOLID MOTORCYCLE DISTRIBUTORS, INC.</h4>
            <h5>Invoice Details</h5>
        </div>

        <div class="info-section">
            <div class="info-card">
                <h6>Invoice Information</h6>
                <p><strong>Invoice Number:</strong> ${escapeHtml(
                  invoice.invoice_number
                )}</p>
                <p><strong>Date Delivered:</strong> ${dateDelivered}</p>
                <p><strong>Notes:</strong> ${escapeHtml(notes)}</p>
            </div>
            <div class="info-card">
                <h6>Summary</h6>
                <p><strong>Motorcycles Count:</strong> ${
                  invoice.motorcycles ? invoice.motorcycles.length : 0
                }</p>
                <p><strong>Generated On:</strong> ${new Date().toLocaleString()}</p>
            </div>
        </div>

        <h6>Motorcycles in this Invoice</h6>
        <table>
            <thead>
                <tr>
                    <th>Brand</th>
                    <th>Model</th>
                    <th>Color</th>
                    <th>Engine Number</th>
                    <th>Frame Number</th>
                    <th>Status</th>
                    <th>Current Branch</th>
                </tr>
            </thead>
            <tbody>
                ${
                  invoice.motorcycles && invoice.motorcycles.length > 0
                    ? invoice.motorcycles
                        .map((moto) => {
                          const statusClass = getStatusBadgeClass(moto.status);
                          return `
                            <tr>
                                <td>${escapeHtml(moto.brand)}</td>
                                <td>${escapeHtml(moto.model)}</td>
                                <td>${escapeHtml(moto.color)}</td>
                                <td><code>${escapeHtml(
                                  moto.engine_number
                                )}</code></td>
                                <td><code>${escapeHtml(
                                  moto.frame_number
                                )}</code></td>
                                <td><span class="badge ${statusClass}">${
                            moto.status
                          }</span></td>
                                <td>${escapeHtml(moto.current_branch)}</td>
                            </tr>
                        `;
                        })
                        .join("")
                    : `<tr><td colspan="7" style="text-align:center;">No motorcycles found in this invoice.</td></tr>`
                }
            </tbody>
        </table>

        <div class="footer">
            Document Reference: ${escapeHtml(invoiceNumber)}<br />
            Generated on: ${new Date().toLocaleString()}
        </div>
    </body>
    </html>
    `;

  const printWindow = window.open("", "_blank");
  printWindow.document.write(html);
  printWindow.document.close();
  printWindow.focus();

  setTimeout(() => {
    printWindow.print();
  }, 300);
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
      transfer_invoice_number: transferInvoiceNumber,
    },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        displayTransferSearchResults(response.data);
      } else {
        showErrorModal(response.message || "No transfer receipt found");
        $("#searchResultsContainer").hide();
      }
    },
    error: function (xhr, status, error) {
      showErrorModal("Error searching transfer receipt: " + error);
      $("#searchResultsContainer").hide();
    },
  });
}

function displayTransferSearchResults(data) {
  const $resultsContainer = $("#transferSearchResults");
  $resultsContainer.empty();

  if (data.length === 0) {
    $resultsContainer.html(
      '<div class="text-center text-muted py-3">No transfer receipts found</div>'
    );
  } else {
    data.forEach((transfer) => {
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

  $(".view-receipt-btn")
    .off("click")
    .on("click", function () {
      const transferId = $(this).data("transfer-id");
      const invoiceNumber = $(this).data("invoice-number");
      loadTransferReceipt(transferId, invoiceNumber);
    });
}

function loadTransferReceipt(transferId, invoiceNumber) {
  $.ajax({
    url: "../api/inventory_management.php",
    method: "GET",
    data: {
      action: "get_transfer_receipt",
      transfer_id: transferId,
    },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        $("#searchTransferReceiptModal").modal("hide");
        showTransferReceipt(response.data);
      } else {
        showErrorModal(response.message || "Error loading transfer receipt");
      }
    },
    error: function (xhr, status, error) {
      showErrorModal("Error loading transfer receipt: " + error);
    },
  });
}

function showTransferReceipt(receiptData) {
  if (!receiptData) return;

  let headerData,
    motorcycles,
    totalCount,
    totalCost,
    notes,
    transferInvoiceNumber;

  if (receiptData.header) {
    headerData = receiptData.header;
    motorcycles = receiptData.motorcycles;
    totalCount = receiptData.total_count;
    totalCost = receiptData.total_cost;
    notes = headerData.notes;
    transferInvoiceNumber = headerData.transfer_invoice_number;
  } else {
    headerData = {
      transfer_date:
        receiptData.transfer_date || new Date().toISOString().split("T")[0],
      from_branch: receiptData.from_branch,
      to_branch: receiptData.to_branch,
      notes: receiptData.notes,
      transfer_invoice_number: receiptData.transfer_invoice_number,
    };
    motorcycles = receiptData.motorcycles;
    totalCount = receiptData.total_count;
    totalCost = receiptData.total_cost;
    notes = receiptData.notes;
    transferInvoiceNumber = receiptData.transfer_invoice_number;
  }

  
  $("#receiptDate").text(formatDate(headerData.transfer_date));
  $("#receiptTransferId").text(headerData.id || "N/A");
  $("#receiptInvoiceNo").text(transferInvoiceNumber || "N/A");
  $("#receiptFromBranch").text(headerData.from_branch);
  $("#receiptToBranch").text(headerData.to_branch);

  
  if (notes && notes.trim() !== "") {
    $("#receiptNotes").text(notes);
  } else {
    $("#receiptNotes").text("No notes provided.");
  }

  
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

  const finalTotalCost = totalCost || calculatedTotalCost;
  const finalTotalCount = totalCount || motorcycles.length;

  $("#receiptTotalCount").text(finalTotalCount);
  $("#receiptTotalCost").text(formatCurrency(finalTotalCost));

  $("#transferReceiptModal").modal("show");

  $("#printReceiptBtn")
    .off("click")
    .on("click", function () {
      printReceipt();
    });
}

function printReceipt() {
  const currentDate = new Date().toISOString().slice(0, 10);
  const title = `Transfer_Receipt_${$(
    "#receiptInvoiceNo"
  ).text()}_${currentDate}`;

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
        size: 80mm auto;
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
                            <span class="info-value">${$(
                              "#receiptDate"
                            ).text()}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Transfer Invoice No:</span>
                            <span class="info-value">${$(
                              "#receiptInvoiceNo"
                            ).text()}</span>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">Branch Information</div>
                    <div class="card-body">
                        <div class="info-row">
                            <span class="info-label">From Branch:</span>
                            <span class="info-value">${$(
                              "#receiptFromBranch"
                            ).text()}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">To Branch:</span>
                            <span class="info-value">${$(
                              "#receiptToBranch"
                            ).text()}</span>
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
                                <td style="font-weight: 600;">${$(
                                  "#receiptTotalCount"
                                ).text()}</td>
                            </tr>
                            <tr class="total-row">
                                <td colspan="6" style="text-align: right; font-weight: 600;">Total Inventory Cost:</td>
                                <td style="font-weight: 600;">${$(
                                  "#receiptTotalCost"
                                ).text()}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            
            <div class="card notes-section">
                <div class="card-header">Transfer Notes</div>
                <div class="card-body">
                    <div>${
                      $("#receiptNotes").text() ||
                      "No additional notes provided."
                    }</div>
                </div>
            </div>
            
           
            
            <div class="footer-info">
                <div>Generated on: ${new Date().toLocaleString()}</div>
                <div>Document Reference: ${$("#receiptInvoiceNo").text()}</div>
                
            </div>
        </body>
        </html>
    `;

  const printWindow = window.open("", "_blank");
  printWindow.document.write(printContent);
  printWindow.document.close();
  printWindow.focus();

  setTimeout(function () {
    printWindow.print();
  }, 250);
}





function fetchModelsForFilter() {
  if (allModelsCache.length > 0) {
    return; 
  }
  $.ajax({
    url: "../api/inventory_management.php",
    method: "GET",
    data: { action: "get_all_models" },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        allModelsCache = response.data;
      } else {
        console.error("Failed to fetch models for filter:", response.message);
      }
    },
    error: function () {
      console.error("AJAX error fetching models for filter.");
    },
  });
}

/**
 * Updates the display of selected model tags in the filter UI.
 */
function renderModelFilterUI() {
  const tagsContainer = $("#selected-models-tags");
  tagsContainer.empty();
  selectedReportModels.forEach((model) => {
    tagsContainer.append(`
            <span class="badge bg-primary d-flex align-items-center">
                ${escapeHtml(model)}
                <button type="button" class="btn-close btn-close-white ms-2" aria-label="Remove" style="font-size: 0.6em;" onclick="removeModelFromSelection(event, '${escapeHtml(
                  model
                )}')"></button>
            </span>
        `);
  });
  $("#reportModelFilter").val(selectedReportModels.join(","));
  $("#reportModelSearch").val("").focus();
  $("#model-search-results").empty().removeClass("show");
}

/**
 * Filters the cached models and displays search results.
 * @param {string} searchTerm The text entered by the user.
 */
function updateModelSearchResults(searchTerm) {
  const resultsContainer = $("#model-search-results");
  resultsContainer.empty().addClass("show");

  if (!searchTerm) {
    resultsContainer.removeClass("show");
    return;
  }

  const filteredModels = allModelsCache.filter(
    (model) =>
      model.toLowerCase().includes(searchTerm.toLowerCase()) &&
      !selectedReportModels.includes(model)
  );

  if (filteredModels.length === 0) {
    resultsContainer.append(
      '<li class="dropdown-item disabled">No models found</li>'
    );
  } else {
    filteredModels.slice(0, 50).forEach((model) => {
      
      resultsContainer.append(
        `<li class="dropdown-item" style="cursor: pointer;" onclick="addModelToSelection('${escapeHtml(
          model
        )}')">${escapeHtml(model)}</li>`
      );
    });
  }
}

/**
 * Adds a model to the selection array and updates the UI.
 * @param {string} modelName The model to add.
 */
function addModelToSelection(modelName) {
  if (!selectedReportModels.includes(modelName)) {
    selectedReportModels.push(modelName);
    renderModelFilterUI();
  }
}

/**
 * Removes a model from the selection array and updates the UI.
 * @param {Event} event The click event.
 * @param {string} modelName The model to remove.
 */
function removeModelFromSelection(event, modelName) {
  event.stopPropagation(); 
  selectedReportModels = selectedReportModels.filter((m) => m !== modelName);
  renderModelFilterUI();
}




function updateReportFilterOptions() {
  const selectedReport = $("#reportType").val();
  const config = reportOptionsConfig[selectedReport];
  const $periodContainer = $("#periodOptionsContainer");

  if (!config) {
    console.error(`No configuration found for report type: ${selectedReport}`);
    return;
  }

  $periodContainer.empty();
  $("#soldSaleTypeContainer").hide();

  let radioHtml = "";
  config.periods.forEach((period, index) => {
    const checked = index === 0 ? "checked" : "";
    const labels = {
      daily: "Daily",
      monthly: "Monthly",
      as_of_date: "As of Date",
      custom_range: "Custom Range",
    };
    radioHtml += `
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="reportPeriodType" id="period_${period}" value="${period}" ${checked}>
                <label class="form-check-label" for="period_${period}">${labels[period]}</label>
            </div>
        `;
  });
  $periodContainer.html(radioHtml);

  
  
  
  if (config.periods.length === 1 && config.periods[0] === "as_of_date") {
    const currentMonthValue = $("#reportMonth").val();
    if (currentMonthValue) {
      const [year, month] = currentMonthValue.split("-");
      
      const lastDay = new Date(year, month, 0).getDate();
      const newAsOfDate = `${year}-${month}-${String(lastDay).padStart(
        2,
        "0"
      )}`;
      $("#asOfDate").val(newAsOfDate);
    }
  }
  

  if (config.filters.includes("sale_type")) {
    $("#soldSaleTypeContainer").show();
  }

  $('input[name="reportPeriodType"]').on("change", updateDatePickerVisibility);
  $('input[name="reportPeriodType"]:checked').trigger("change");
}

function updateDatePickerVisibility() {
  $(
    "#dailyDatePickerContainer, #monthPickerContainer, #asOfDatePickerContainer, #customDateRangeContainer"
  ).hide();

  const selectedPeriod = $('input[name="reportPeriodType"]:checked').val();

  switch (selectedPeriod) {
    case "daily":
      $("#dailyDatePickerContainer").show();
      break;
    case "monthly":
      $("#monthPickerContainer").show();
      break;
    case "as_of_date":
      $("#asOfDatePickerContainer").show();
      break;
    case "custom_range":
      $("#customDateRangeContainer").show();
      break;
  }
}


function showMonthlyReportOptions() {
  
  selectedReportModels = [];
  renderModelFilterUI();
  fetchModelsForFilter(); 

  
  const now = new Date();
  const currentMonth =
    now.getFullYear() + "-" + String(now.getMonth() + 1).padStart(2, "0");
  $("#reportMonth").val(currentMonth);

  
  if (
    currentUserBranch === "HEADOFFICE", "ADMIN" ||
    ["IT STAFF", "HEAD"].includes(currentUserPosition)
  ) {
    populateBranchesDropdown();
    $("#reportBranch").prop("disabled", false);
    $("#brandFilterContainer").show();
    $("#reportBrandFilter").prop("disabled", false);
  } else {
    $("#reportBranch")
      .empty()
      .append(
        `<option value="${currentUserBranch}">${currentUserBranch}</option>`
      );
    $("#reportBranch").val(currentUserBranch).prop("disabled", true);
    $("#brandFilterContainer").hide();
    $("#reportBrandFilter").prop("disabled", true);
  }

  $("#monthlyReportOptionsModal").modal("show");
}


function generateReport() {
  const reportType = $("#reportType").val();
  const periodType = $('input[name="reportPeriodType"]:checked').val();

  const filters = {
    branch: $("#reportBranch").val() || "all",
    category: $("#reportCategoryFilter").val() || "all",
    brand: $("#reportBrandFilter").val() || "all",
    model: $("#reportModelFilter").val() || "all",
    sale_type: $("#soldSaleTypeFilter").val() || "all",
  };
  const apiData = {
    action: "",
    period_type: periodType, 
    ...filters,
  };

  switch (periodType) {
    case "daily":
      apiData.date = formatDateForAPI($("#dailyDate").val());
      if (!apiData.date) {
        showErrorModal("Please select a date.");
        return;
      }
      break;
    case "monthly":
      apiData.month = $("#reportMonth").val();
      if (!apiData.month) {
        showErrorModal("Please select a month.");
        return;
      }
      break;
    case "as_of_date":
      apiData.date = formatDateForAPI($("#asOfDate").val());
      if (!apiData.date) {
        showErrorModal('Please select an "as of" date.');
        return;
      }
      break;
    case "custom_range":
      apiData.start_date = formatDateForAPI($("#startDate").val());
      apiData.end_date = formatDateForAPI($("#endDate").val());
      if (!apiData.start_date || !apiData.end_date) {
        showErrorModal("Please select a start and end date.");
        return;
      }
      break;
    default:
      break;
  }
  $("#monthlyReportOptionsModal").modal("hide");
  $("#monthlyReportContent").html(
    '<div class="text-center py-5"><div class="spinner-border text-black" role="status"></div></div>'
  );
  switch (reportType) {
    case "inventory":
      apiData.action = "get_monthly_inventory";
      callReportAPI(apiData, renderMonthlyInventoryReport, reportType);
      break;
    case "inventory_summary":
      
      apiData.action = "get_monthly_inventory";

      
      callReportAPI(apiData, renderInventorySummaryReport, reportType);
      break;
    case "sold_units":
      apiData.action = "get_sold_motorcycles_report";
      callReportAPI(apiData, renderSoldUnitsReport, reportType);
      break;
    case "transferred":
      apiData.action = "get_monthly_transferred_summary";
      callReportAPI(apiData, renderTransferredSummaryReport, reportType);
      break;
    case "received":
      apiData.action = "get_monthly_received_summary";
      callReportAPI(apiData, renderReceivedSummaryReport, reportType);
      break;
    case "delivered_stocks":
      apiData.action = "get_delivered_stocks_summary";
      callReportAPI(apiData, renderDeliveredSummaryReport, reportType);
      break;
    case "scrapped":
      apiData.action = "get_monthly_scrapped_summary";
      callReportAPI(apiData, renderScrappedReport, reportType);
      break;
    case "redeemed":
      apiData.action = "get_redeemed_units_report";
      callReportAPI(apiData, renderRedeemedReport, reportType);
      break;
    case "motorcycle":
      apiData.action = "get_available_motorcycles_report"; 
      
      callReportAPI(apiData, renderMotorcycleReport, reportType);
      break;
    default:
      showErrorModal("Invalid report type selected.");
      $("#monthlyReportContent").empty();
      break;
  }
}
function callReportAPI(apiData, renderFunction, reportType) {
  $.ajax({
    url: "../api/inventory_management.php",
    method: "GET",
    data: apiData,
    dataType: "json",
    success: function (response) {
      if (response.success) {
        currentReportData = response;
        currentReportType = reportType;
        currentReportSummary = response.summary;
        currentReportMonth = response.month || apiData.month;

        
        currentReportDate =
          response.as_of_date || response.date || apiData.date;

        currentReportStartDate = response.start_date || apiData.start_date;
        currentReportEndDate = response.end_date || apiData.end_date;
        currentReportBranch = response.branch;
        currentReportCategory = apiData.category;
        currentReportBrand = apiData.brand;
        currentReportModel = apiData.model;
        currentReportSaleType = apiData.sale_type;

        renderFunction(response);
        $("#monthlyInventoryReportModal").modal("show");
      } else {
        showErrorModal(response.message || "Error generating report");
        $("#monthlyReportContent").empty();
      }
    },
    error: function (xhr, status, error) {
      showErrorModal("Error generating report: " + error);
      $("#monthlyReportContent").empty();
    },
  });
}
function generateReportPDF() {
  if (!currentReportData || !currentReportType) {
    showErrorModal("Please generate a report first before exporting to PDF");
    return;
  }

  if (currentReportType === "inventory") {
    generateInventoryReportPDF();
  } else if (currentReportType === "transferred") {
    generateTransferredReportPDF();
  } else if (currentReportType === "motorcycle") {
    generateMotorcycleReportPDF();
  } else if (currentReportType === "sold_units") {
    generateSoldUnitsReportPDF();
  } else if (currentReportType === "daily_sold_units") {
    generateDailySoldUnitsReportPDF();
  } else if (currentReportType === "scrapped") {
    generateScrappedReportPDF();
  } else if (currentReportType === "redeemed") {
    generateRedeemedReportPDF();
  } else if (currentReportType === "received") {
    generateReceivedReportPDF();
  } else if (currentReportType === "delivered_stocks") {
    generateDeliveredReportPDF();
  } else if (currentReportType === "inventory_summary") {
    generateInventorySummaryReportPDF();
  } else {
    showErrorModal("PDF export not available for this report type");
  }
}


function generateMonthlyInventoryReport(
  month,
  branch,
  category = "all",
  brand = "all",
  date = null
) {
  $("#monthlyInventoryOptionsModal").modal("hide");
  $("#monthlyReportContent").html(
    '<div class="text-center py-5"><div class="spinner-border text-black" role="status"></div></div>'
  );

  const apiData = {
    action: "get_monthly_inventory",
    branch: branch || "all",
    category: category,
    brand: brand,
  };

  if (date) {
    apiData.date = date; 
  } else {
    apiData.month = month; 
  }

  $.ajax({
    url: "../api/inventory_management.php",
    method: "GET",
    data: apiData,
    dataType: "json",
    success: function (response) {
      if (response.success) {
        currentReportData = response.data;
        currentReportMonth = response.month;
        currentReportBranch = response.branch;
        currentReportSummary = response.summary;
        currentReportType = "inventory";
        currentReportDate = response.as_of_date; 

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
    },
  });
}

function renderMonthlyInventoryReport(response) {
  
  const { data, summary } = response;
  const isRepoReport = currentReportCategory === "repo"; 

  let reportTitle = "Inventory Balance Report";
  let dateSubtitle = "";

  
  if (currentReportDate) {
    dateSubtitle = `As of ${formatDate(currentReportDate)}`;
  } else if (currentReportMonth && currentReportMonth.includes("-")) {
    const [year, monthNum] = currentReportMonth.split("-");
    const monthName = new Date(year, monthNum - 1, 1).toLocaleString(
      "default",
      { month: "long" }
    );
    reportTitle = "Monthly Inventory Balance Report";
    dateSubtitle = `For the Month of ${monthName} ${year}`;
  }

  $("#monthlyInventoryReportModalLabel").text(reportTitle);

  if (Array.isArray(data)) {
    data.sort((a, b) => a.model.localeCompare(b.model));
  }

  const beginningBalance = summary?.beginning_balance || 0;
  const costBeginning = summary?.inventory_cost?.beginning_balance || 0;
  const receivedTransfers = summary?.received_transfers || 0;
  const newDeliveries = summary?.new_deliveries || 0;
  const totalIn = summary?.in || 0;
  const transfersOut = summary?.transfers_out || 0;
  const soldDuringMonth = summary?.sold_during_month || 0;
  const totalOut = summary?.out || 0;
  const endingActual = summary?.ending_actual || 0;
  const costReceived = summary?.inventory_cost?.received_transfers || 0;
  const costNewDeliveries = summary?.inventory_cost?.new_deliveries || 0;
  const costTotalIn = summary?.inventory_cost?.in || 0;
  const costTransfersOut = summary?.inventory_cost?.transfers_out || 0;
  const costSoldDuringMonth = summary?.inventory_cost?.sold_during_month || 0;
  const costTotalOut = summary?.inventory_cost?.out || 0;
  const costEndingActual = summary?.inventory_cost?.ending_actual || 0;

  let html = `
    <div class="report-header text-center mb-4">
      <div class="d-flex align-items-center justify-content-center mb-2">
        <div style="width: 40px; height: 2px; background: #000f71; margin-right: 15px;"></div>
        <h4 class="mb-0" style="color: #000f71; font-weight: 600;">SOLID MOTORCYCLE DISTRIBUTORS, INC.</h4>
        <div style="width: 40px; height: 2px; background: #000f71; margin-left: 15px;"></div>
      </div>
      <h5 class="mb-2" style="color: #495057;">${reportTitle}</h5>
      <h6 class="mb-2 text-muted">${dateSubtitle}</h6>
      ${buildFilterDisplayHtml()}
    </div>
    
    <div class="row">
      <div class="col-lg-8">
        <div class="table-responsive">
          <table class="table table-sm table-striped table-hover">
            <thead class="table-dark" style="position: sticky; top: 0; z-index: 10;">
              <tr>
                <th>QTY</th>
                <th>MODEL</th>
                <th>COLOR</th>
                <th>BRAND</th>
                <th>ENGINE NUMBER</th>
                <th>FRAME NUMBER</th>
                <th class="text-end">Inventory Cost</th>
                ${
                  isRepoReport
                    ? `
                <th>CUSTOMER NAME</th>
                <th>DATE SOLD</th>
                `
                    : ""
                }
              </tr>
            </thead>
            <tbody>
  `;

  if (!data || data.length === 0) {
    const colspan = isRepoReport ? 9 : 7;
    html += `<tr><td colspan="${colspan}" class="text-center py-5 text-muted">No inventory data found.</td></tr>`;
  } else {
    data.forEach((item) => {
      html += `
        <tr>
          <td class="text-center">1</td>
          <td>${escapeHtml(item.model)}</td>
          <td>${escapeHtml(item.color)}</td>
          <td>${escapeHtml(item.brand)}</td>
          <td><code>${escapeHtml(item.engine_number)}</code></td>
          <td><code>${escapeHtml(item.frame_number)}</code></td>
          <td class="text-end">${formatCurrency(item.inventory_cost)}</td>
          ${
            isRepoReport
              ? `
          <td>${escapeHtml(item.customer_name || "-")}</td>
          <td>${
            item.date_sold && item.date_sold !== "-"
              ? formatDate(item.date_sold)
              : "-"
          }</td>
          `
              : ""
          }
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
        <div class="card border-0 shadow-sm mb-3" style="border-radius: 8px;">
              <div class="card-header bg-transparent border-0 pt-3 pb-2">
                  <h6 class="card-title text-center mb-0" style="color: #6c757d; font-weight: 600; font-size: 0.9rem;">
                      BEGINNING BALANCE
                  </h6>
              </div>
              <div class="card-body px-4 pb-3 pt-0">
                  <div class="summary-item d-flex justify-content-between align-items-center pt-2">
                      <div>
                          <div class="fw-bold" style="color: #6c757d;">Balance Forward</div>
                      </div>
                      <div class="text-end">
                          <span class="fs-4 fw-bold" style="color: #6c757d;">${beginningBalance}</span>
                          <div class="small text-muted fw-bold">${formatCurrency(
                            costBeginning
                          )}</div>
                      </div>
                  </div>
              </div>
          </div>
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
                <div class="small text-muted">${formatCurrency(
                  costReceived
                )}</div>
              </div>
            </div>
            
            <div class="summary-item d-flex justify-content-between align-items-center mb-2 pb-2" style="border-bottom: 1px solid #f1f3f4;">
              <div>
                <div class="fw-semibold small" style="color: #495057;">New Deliveries</div>
              </div>
              <div class="text-end">
                <span class="fw-bold" style="color: #28a745;">${newDeliveries}</span>
                <div class="small text-muted">${formatCurrency(
                  costNewDeliveries
                )}</div>
              </div>
            </div>
            
            <div class="summary-item d-flex justify-content-between align-items-center pt-2">
              <div>
                <div class="fw-bold" style="color: #28a745;">TOTAL IN</div>
              </div>
              <div class="text-end">
                <span class="fs-5 fw-bold" style="color: #28a745;">${totalIn}</span>
                <div class="small text-success fw-bold">${formatCurrency(
                  costTotalIn
                )}</div>
              </div>
            </div>
          </div>
        </div>

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
                <div class="small text-muted">${formatCurrency(
                  costTransfersOut
                )}</div>
              </div>
            </div>
            
            <div class="summary-item d-flex justify-content-between align-items-center mb-2 pb-2" style="border-bottom: 1px solid #f1f3f4;">
              <div>
                <div class="fw-semibold small" style="color: #495057;">Sold During Month</div>
              </div>
              <div class="text-end">
                <span class="fw-bold" style="color: #dc3545;">${soldDuringMonth}</span>
                <div class="small text-muted">${formatCurrency(
                  costSoldDuringMonth
                )}</div>
              </div>
            </div>
            
            <div class="summary-item d-flex justify-content-between align-items-center pt-2">
              <div>
                <div class="fw-bold" style="color: #dc3545;">TOTAL OUT</div>
              </div>
              <div class="text-end">
                <span class="fs-5 fw-bold" style="color: #dc3545;">${totalOut}</span>
                <div class="small text-danger fw-bold">${formatCurrency(
                  costTotalOut
                )}</div>
              </div>
            </div>
          </div>
        </div>

        <div class="card border-0 shadow-sm mb-3" style="border-radius: 8px;">
          <div class="card-header bg-transparent border-0 pt-3 pb-2">
            <h6 class="card-title text-center mb-0" style="color: #000f71; font-weight: 600; font-size: 0.9rem;">
              ENDING BALANCE
            </h6>
          </div>
          <div class="card-body px-4 pb-3 pt-0">
            <div class="summary-item d-flex justify-content-between align-items-center pt-2">
              <div>
                <div class="fw-bold" style="color: #000f71;">Actual</div>
              </div>
              <div class="text-end">
                <span class="fs-4 fw-bold" style="color: #000f71;">${endingActual}</span>
                <div class="small text-black fw-bold">${formatCurrency(
                  costEndingActual
                )}</div>
              </div>
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
  html += generateBrandSummaryHtml(data);
  $("#monthlyReportContent").html(html);

  $("<style>")
    .prop("type", "text/css")
    .html(
      `
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
    `
    )
    .appendTo("head");
}


function generateInventoryReportPDF() {
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF({
    orientation: "landscape", 
  });

  const pageWidth = doc.internal.pageSize.getWidth();
  const pageHeight = doc.internal.pageSize.getHeight();

  
  const isRepoReport = currentReportCategory === "repo";

  
  let reportTitle = "Inventory Balance Report";
  let dateSubtitle = "";

  if (currentReportDate) {
    dateSubtitle = `As of ${formatDate(currentReportDate)}`;
  } else if (currentReportMonth) {
    const [year, monthNum] = currentReportMonth.split("-");
    const monthName = new Date(year, monthNum - 1, 1).toLocaleString(
      "default",
      {
        month: "long",
      }
    );
    reportTitle = "Monthly Inventory Balance Report";
    dateSubtitle = `For the Month of ${monthName} ${year}`;
  }
  

  const totalIn = currentReportSummary?.in || 0;
  const totalOut = currentReportSummary?.out || 0;
  const endingActual = currentReportSummary?.ending_actual || 0;
  const costTotalIn = currentReportSummary?.inventory_cost?.in || 0;
  const costTotalOut = currentReportSummary?.inventory_cost?.out || 0;
  const costEndingActual =
    currentReportSummary?.inventory_cost?.ending_actual || 0;

  
  const receivedTransfers = currentReportSummary?.received_transfers || 0;
  const newDeliveries = currentReportSummary?.new_deliveries || 0;
  const transfersOut = currentReportSummary?.transfers_out || 0;
  const soldDuringMonth = currentReportSummary?.sold_during_month || 0;

  currentReportData.data.sort((a, b) => a.model.localeCompare(b.model));

  doc.setFont("helvetica", "bold");
  doc.setFontSize(14);
  doc.setTextColor(0, 15, 113);
  doc.text("SOLID MOTORCYCLE DISTRIBUTORS, INC.", pageWidth / 2, 15, {
    align: "center",
  });

  doc.setFontSize(12);
  doc.setTextColor(73, 80, 87);
  doc.text(reportTitle, pageWidth / 2, 25, { align: "center" });

  doc.setFontSize(10);
  doc.setTextColor(0, 64, 133);
  doc.text(dateSubtitle, pageWidth / 2, 33, { align: "center" });

  let currentY = 38;
  currentY = addFiltersToPdf(doc, currentY);

  
  const columns = [
    { header: "QTY", dataKey: "qty" },
    { header: "MODEL", dataKey: "model" },
    { header: "COLOR", dataKey: "color" },
    { header: "BRAND", dataKey: "brand" },
    { header: "ENGINE NUMBER", dataKey: "engine_number" },
    { header: "FRAME NUMBER", dataKey: "frame_number" },
    { header: "Inventory Cost", dataKey: "inventory_cost" },
  ];

  
  if (isRepoReport) {
    columns.push({ header: "CUSTOMER NAME", dataKey: "customer_name" });
    columns.push({ header: "DATE SOLD", dataKey: "date_sold" });
  }

  
  const rows =
    !currentReportData.data || currentReportData.data.length === 0
      ? [
          {
            qty: {
              content: "No inventory data found for this period",
              colSpan: isRepoReport ? 9 : 7, 
              styles: { halign: "center" },
            },
          },
        ]
      : currentReportData.data.map((item) => {
          const rowData = {
            qty: "1",
            model: item.model,
            color: item.color,
            brand: item.brand,
            engine_number: item.engine_number,
            frame_number: item.frame_number,
            inventory_cost: formatCurrency(item.inventory_cost),
          };

          if (isRepoReport) {
            rowData.customer_name = item.customer_name || "-";
            rowData.date_sold =
              item.date_sold && item.date_sold !== "-"
                ? formatDate(item.date_sold)
                : "-";
          }
          return rowData;
        });

  function formatCurrency(amount) {
    if (amount == null || amount === "") return "N/A";
    return Number(amount).toLocaleString("en-US", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
  }

  doc.autoTable({
    startY: currentY,
    headStyles: {
      fillColor: [248, 249, 250],
      textColor: [73, 80, 87],
      fontStyle: "bold",
    },
    styles: { fontSize: 8, cellPadding: 2 },
    columnStyles: {
      qty: { halign: "center" },
      inventory_cost: { halign: "right" },
    },
    columns: columns.map((c) => c.header), 
    body: rows.map((row) => columns.map((c) => row[c.dataKey])), 
    margin: { left: 10, right: 10 },
  });

  let finalTableY = doc.autoTable.previous.finalY;

  if (!finalTableY || isNaN(finalTableY)) {
    finalTableY = 50;
  }

  
  const cardMargin = 8;
  const leftRightMargin = 10;
  const topMargin = 20;
  const bottomMargin = 20;
  const cardHeight = 45;
  const spaceAfterTable = 10;
  const cardWidth = (pageWidth - 2 * leftRightMargin - 2 * cardMargin) / 3;
  const summarySectionHeight = cardHeight + 10;

  currentY = finalTableY + spaceAfterTable;

  if (currentY + summarySectionHeight + bottomMargin > pageHeight) {
    doc.addPage();
    currentY = topMargin;
  }

  function drawCard(
    x,
    y,
    cardWidth,
    cardHeight,
    title,
    mainValue,
    subValue,
    mainColor,
    subColor,
    extraText
  ) {
    doc.setDrawColor(233, 236, 239);
    doc.setFillColor(248, 249, 250);
    doc.rect(x, y, cardWidth, cardHeight, "F");

    doc
      .setFontSize(9)
      .setTextColor(73, 80, 87)
      .setFont("helvetica", "bold")
      .text(title, x + cardWidth / 2, y + 8, { align: "center" });
    doc
      .setFontSize(16)
      .setTextColor(...mainColor)
      .setFont("helvetica", "bold")
      .text(String(mainValue), x + cardWidth / 2, y + 25, { align: "center" });
    doc
      .setFontSize(10)
      .setTextColor(...subColor)
      .setFont("helvetica", "normal");

    const subValueLines = doc.splitTextToSize(String(subValue), cardWidth - 10);
    doc.text(subValueLines, x + cardWidth / 2, y + 33, { align: "center" });

    if (extraText) {
      doc.setFontSize(7).setTextColor(73, 80, 87);
      const extraTextLines = doc.splitTextToSize(extraText, cardWidth - 10);
      doc.text(extraTextLines, x + cardWidth / 2, y + 40, { align: "center" });
    }
  }

  drawCard(
    leftRightMargin,
    currentY,
    cardWidth,
    cardHeight,
    "IN",
    totalIn,
    formatCurrency(costTotalIn),
    [40, 167, 69],
    [40, 167, 69],
    `Received: ${receivedTransfers} | New: ${newDeliveries}`
  );
  drawCard(
    leftRightMargin + (cardWidth + cardMargin),
    currentY,
    cardWidth,
    cardHeight,
    "OUT",
    totalOut,
    formatCurrency(costTotalOut),
    [220, 53, 69],
    [220, 53, 69],
    `Transferred: ${transfersOut} | Sold: ${soldDuringMonth}`
  );
  drawCard(
    leftRightMargin + 2 * (cardWidth + cardMargin),
    currentY,
    cardWidth,
    cardHeight,
    "ENDING BALANCE",
    endingActual,
    formatCurrency(costEndingActual),
    [0, 64, 133],
    [0, 86, 179],
    null
  );

  currentY += cardHeight + 10;

  currentY = addBrandSummaryToPdf(doc, currentReportData.data, currentY);

  const now = new Date();
  const generatedOn = now.toLocaleDateString("en-US", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  });
  const totalPages = doc.internal.getNumberOfPages();

  for (let i = 1; i <= totalPages; i++) {
    doc.setPage(i);
    doc.setFontSize(8);
    doc.setTextColor(108, 117, 125);
    doc.text(`Generated on: ${generatedOn}`, 10, pageHeight - 10);
    doc.text(`Page ${i} of ${totalPages}`, pageWidth / 2, pageHeight - 10, {
      align: "center",
    });
  }

  doc.save(
    `Monthly_Inventory_Report_${
      currentReportMonth || currentReportDate
    }_${currentReportBranch}.pdf`
  );
}


function generateInventoryReportPDF() {
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF({
    orientation: "landscape",
  });

  const pageWidth = doc.internal.pageSize.getWidth();
  const pageHeight = doc.internal.pageSize.getHeight();
  const isRepoReport = currentReportCategory === "repo";

  
  let reportTitle = "Inventory Balance Report";
  let dateSubtitle = "";

  if (currentReportDate) {
    dateSubtitle = `As of ${formatDate(currentReportDate)}`;
  } else if (currentReportMonth) {
    const [year, monthNum] = currentReportMonth.split("-");
    const monthName = new Date(year, monthNum - 1, 1).toLocaleString(
      "default",
      { month: "long" }
    );
    reportTitle = "Monthly Inventory Balance Report";
    dateSubtitle = `For the Month of ${monthName} ${year}`;
  }

  
  const beginningBalance = currentReportSummary?.beginning_balance || 0;
  const costBeginning =
    currentReportSummary?.inventory_cost?.beginning_balance || 0;
  const totalIn = currentReportSummary?.in || 0;
  const totalOut = currentReportSummary?.out || 0;
  const endingActual = currentReportSummary?.ending_actual || 0;
  const costTotalIn = currentReportSummary?.inventory_cost?.in || 0;
  const costTotalOut = currentReportSummary?.inventory_cost?.out || 0;
  const costEndingActual =
    currentReportSummary?.inventory_cost?.ending_actual || 0;
  const receivedTransfers = currentReportSummary?.received_transfers || 0;
  const newDeliveries = currentReportSummary?.new_deliveries || 0;
  const transfersOut = currentReportSummary?.transfers_out || 0;
  const soldDuringMonth = currentReportSummary?.sold_during_month || 0;

  currentReportData.data.sort((a, b) => a.model.localeCompare(b.model));

  
  doc.setFont("helvetica", "bold");
  doc.setFontSize(14);
  doc.setTextColor(0, 15, 113);
  doc.text("SOLID MOTORCYCLE DISTRIBUTORS, INC.", pageWidth / 2, 15, {
    align: "center",
  });
  doc.setFontSize(12);
  doc.setTextColor(73, 80, 87);
  doc.text(reportTitle, pageWidth / 2, 25, { align: "center" });
  doc.setFontSize(10);
  doc.setTextColor(0, 64, 133);
  doc.text(dateSubtitle, pageWidth / 2, 33, { align: "center" });
  let currentY = 38;
  currentY = addFiltersToPdf(doc, currentY);

  
  const columns = [
    { header: "QTY", dataKey: "qty" },
    { header: "MODEL", dataKey: "model" },
    { header: "COLOR", dataKey: "color" },
    { header: "BRAND", dataKey: "brand" },
    { header: "ENGINE NUMBER", dataKey: "engine_number" },
    { header: "FRAME NUMBER", dataKey: "frame_number" },
    { header: "Inventory Cost", dataKey: "inventory_cost" },
  ];
  if (isRepoReport) {
    columns.push({ header: "CUSTOMER NAME", dataKey: "customer_name" });
    columns.push({ header: "DATE SOLD", dataKey: "date_sold" });
  }
  const rows =
    !currentReportData.data || currentReportData.data.length === 0
      ? [
          {
            qty: {
              content: "No inventory data found for this period",
              colSpan: isRepoReport ? 9 : 7,
              styles: { halign: "center" },
            },
          },
        ]
      : currentReportData.data.map((item) => {
          const rowData = {
            qty: "1",
            model: item.model,
            color: item.color,
            brand: item.brand,
            engine_number: item.engine_number,
            frame_number: item.frame_number,
            inventory_cost: formatCurrency(item.inventory_cost),
          };
          if (isRepoReport) {
            rowData.customer_name = item.customer_name || "-";
            rowData.date_sold =
              item.date_sold && item.date_sold !== "-"
                ? formatDate(item.date_sold)
                : "-";
          }
          return rowData;
        });
  function formatCurrency(amount) {
    if (amount == null || amount === "") return "N/A";
    return Number(amount).toLocaleString("en-US", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
  }
  doc.autoTable({
    startY: currentY,
    headStyles: {
      fillColor: [248, 249, 250],
      textColor: [73, 80, 87],
      fontStyle: "bold",
    },
    styles: { fontSize: 8, cellPadding: 2 },
    columnStyles: {
      qty: { halign: "center" },
      inventory_cost: { halign: "right" },
    },
    columns: columns.map((c) => c.header),
    body: rows.map((row) => columns.map((c) => row[c.dataKey])),
    margin: { left: 10, right: 10 },
  });
  let finalTableY = doc.autoTable.previous.finalY;
  if (!finalTableY || isNaN(finalTableY)) {
    finalTableY = 50;
  }

  
  const cardMargin = 10;
  const leftRightMargin = 10;
  const topMargin = 20;
  const bottomMargin = 20;
  const cardHeight = 35;
  const spaceAfterTable = 10;
  const cardWidth = (pageWidth - 2 * leftRightMargin - cardMargin) / 2;
  const summarySectionHeight = cardHeight * 2 + cardMargin + 10;

  currentY = finalTableY + spaceAfterTable;
  if (currentY + summarySectionHeight + bottomMargin > pageHeight) {
    doc.addPage();
    currentY = topMargin;
  }

  function drawCard(
    x,
    y,
    cardWidth,
    cardHeight,
    title,
    mainValue,
    subValue,
    mainColor,
    subColor,
    extraText
  ) {
    doc.setDrawColor(233, 236, 239);
    doc.setFillColor(248, 249, 250);
    doc.rect(x, y, cardWidth, cardHeight, "F");
    doc
      .setFontSize(9)
      .setTextColor(73, 80, 87)
      .setFont("helvetica", "bold")
      .text(title, x + cardWidth / 2, y + 8, { align: "center" });
    doc
      .setFontSize(16)
      .setTextColor(...mainColor)
      .setFont("helvetica", "bold")
      .text(String(mainValue), x + cardWidth / 2, y + 20, { align: "center" });
    doc
      .setFontSize(9)
      .setTextColor(...subColor)
      .setFont("helvetica", "normal");
    const subValueLines = doc.splitTextToSize(String(subValue), cardWidth - 10);
    doc.text(subValueLines, x + cardWidth / 2, y + 27, { align: "center" });
    if (extraText) {
      doc.setFontSize(7).setTextColor(73, 80, 87);
      const extraTextLines = doc.splitTextToSize(extraText, cardWidth - 10);
      doc.text(extraTextLines, x + cardWidth / 2, y + 32, { align: "center" });
    }
  }

  
  drawCard(
    leftRightMargin,
    currentY,
    cardWidth,
    cardHeight,
    "BEGINNING BALANCE",
    beginningBalance,
    formatCurrency(costBeginning),
    [108, 117, 125],
    [108, 117, 125],
    null
  );
  drawCard(
    leftRightMargin + cardWidth + cardMargin,
    currentY,
    cardWidth,
    cardHeight,
    "IN",
    totalIn,
    formatCurrency(costTotalIn),
    [40, 167, 69],
    [40, 167, 69],
    `Received: ${receivedTransfers} | New: ${newDeliveries}`
  );

  
  currentY += cardHeight + cardMargin;

  
  drawCard(
    leftRightMargin,
    currentY,
    cardWidth,
    cardHeight,
    "OUT",
    totalOut,
    formatCurrency(costTotalOut),
    [220, 53, 69],
    [220, 53, 69],
    `Transferred: ${transfersOut} | Sold: ${soldDuringMonth}`
  );
  drawCard(
    leftRightMargin + cardWidth + cardMargin,
    currentY,
    cardWidth,
    cardHeight,
    "ENDING BALANCE",
    endingActual,
    formatCurrency(costEndingActual),
    [0, 64, 133],
    [0, 86, 179],
    null
  );

  currentY += cardHeight + 10;
  currentY = addBrandSummaryToPdf(doc, currentReportData.data, currentY);

  
  const now = new Date();
  const generatedOn = now.toLocaleDateString("en-US", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  });
  const totalPages = doc.internal.getNumberOfPages();
  for (let i = 1; i <= totalPages; i++) {
    doc.setPage(i);
    doc.setFontSize(8);
    doc.setTextColor(108, 117, 125);
    doc.text(`Generated on: ${generatedOn}`, 10, pageHeight - 10);
    doc.text(`Page ${i} of ${totalPages}`, pageWidth / 2, pageHeight - 10, {
      align: "center",
    });
  }

  doc.save(
    `Monthly_Inventory_Report_${
      currentReportMonth || currentReportDate
    }_${currentReportBranch}.pdf`
  );
}


function toggleInventorySummaryDetails(element, brand, model, branch) {
  const row = $(element);

  const detailsRowId = `details-${brand.replace(/\W/g, "")}-${model.replace(
    /\W/g,
    ""
  )}-${branch.replace(/\W/g, "")}`;
  const existingDetailsRow = $(`#${detailsRowId}`);
  if (existingDetailsRow.length) {
    existingDetailsRow.toggle();
    row.toggleClass("table-active");
    return;
  }

  row.toggleClass("table-active");
  row.after(
    `<tr id="${detailsRowId}" class="model-details-row"><td colspan="100%" class="p-2 bg-light"><div class="text-center"><div class="spinner-border spinner-border-sm"></div> Loading units...</div></td></tr>`
  );

  const units = currentReportData.data.filter(
    (item) =>
      item.brand === brand &&
      item.model === model &&
      (branch === "all" || item.current_branch === branch)
  );

  if (units.length > 0) {
    let detailsHtml =
      '<div class="table-responsive"><table class="table table-sm table-bordered bg-white mb-0"><thead>';
    detailsHtml +=
      '<tr class="table-secondary"><th>QTY</th><th>MODEL</th><th>COLOR</th><th>BRAND</th><th>ENGINE NUMBER</th><th>FRAME NUMBER</th><th class="text-end">Inventory Cost</th></tr></thead><tbody>';
    units.forEach((item) => {
      detailsHtml += `<tr>
                <td class="text-center">1</td>
                <td>${escapeHtml(item.model)}</td>
                <td>${escapeHtml(item.color)}</td>
                <td>${escapeHtml(item.brand)}</td>
                <td>${escapeHtml(item.engine_number)}</td>
                <td>${escapeHtml(item.frame_number)}</td>
                <td class="text-end">${formatCurrency(item.inventory_cost)}</td>
            </tr>`;
    });
    detailsHtml += "</tbody></table></div>";
    $(`#${detailsRowId} td`).html(detailsHtml);
  } else {
    $(`#${detailsRowId} td`).html(
      '<div class="text-center text-muted p-3">No detailed unit data found.</div>'
    );
  }
}

function renderInventorySummaryReport(response) {
  const { data, summary } = response;
  const reportTitle = "Inventory Summary Report";
  let dateSubtitle = "";

  if (currentReportDate) {
    dateSubtitle = `As of ${formatDate(currentReportDate)}`;
  } else if (currentReportMonth) {
    const [year, monthNum] = currentReportMonth.split("-");
    const monthName = new Date(year, monthNum - 1, 1).toLocaleString(
      "default",
      { month: "long" }
    );
    dateSubtitle = `For the Month of ${monthName} ${year}`;
  }

  $("#monthlyInventoryReportModalLabel").text(reportTitle);

  const branches = [...new Set(data.map((item) => item.current_branch))].sort();
  const brandNames = ["Suzuki", "Honda", "Yamaha", "Kawasaki"];
  const brands = {};
  const models_by_brand = {};
  brandNames.forEach((b) => {
    brands[b] = {};
    models_by_brand[b] = {};
  });

  for (const item of data) {
    const { brand, model, current_branch, inventory_cost } = item;
    if (brandNames.includes(brand)) {
      if (!brands[brand][current_branch])
        brands[brand][current_branch] = { count: 0, cost: 0 };
      brands[brand][current_branch].count++;
      brands[brand][current_branch].cost += parseFloat(inventory_cost || 0);

      if (!models_by_brand[brand][model]) models_by_brand[brand][model] = {};
      if (!models_by_brand[brand][model][current_branch])
        models_by_brand[brand][model][current_branch] = { count: 0, cost: 0 };
      models_by_brand[brand][model][current_branch].count++;
      models_by_brand[brand][model][current_branch].cost += parseFloat(
        inventory_cost || 0
      );
    }
  }

  let mainContentHtml = `
        <div class="report-header text-center mb-4">
            <div class="d-flex align-items-center justify-content-center mb-2">
                <div style="width: 40px; height: 2px; background: #000f71; margin-right: 15px;"></div>
                <h4 class="mb-0" style="color: #000f71; font-weight: 600;">SOLID MOTORCYCLE DISTRIBUTORS, INC.</h4>
                <div style="width: 40px; height: 2px; background: #000f71; margin-left: 15px;"></div>
            </div>
            <h5 class="mb-2" style="color: #495057;">${reportTitle}</h5>
            <h6 class="mb-2 text-muted">${dateSubtitle}</h6>
        </div>`;

  if (isHeadOffice || isAdminUser) {
    
    let navTabsHtml = `<ul class="nav nav-tabs" role="tablist">
            <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#summary-sheet" type="button">Summary</button></li>`;
    brandNames.forEach((brand) => {
      navTabsHtml += `<li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#${brand.toLowerCase()}-sheet" type="button">${brand}</button></li>`;
    });
    navTabsHtml += `</ul>`;

    let tabContentHtml = `<div class="tab-content border border-top-0 p-3">`;
    let summaryTable = `<div class="table-responsive mt-2"><table class="table table-bordered table-sm table-hover"><thead><tr><th>BRAND</th>`;
    branches.forEach((b) => {
      summaryTable += `<th>${escapeHtml(b)}</th>`;
    });
    summaryTable += `<th>GRAND TOTAL</th></tr></thead><tbody>`;
    const branchTotals = { count: {}, cost: {} };
    let grandTotalAll = { count: 0, cost: 0 };
    brandNames.forEach((brand) => {
      summaryTable += `<tr><td><strong>${brand}</strong></td>`;
      let brandTotal = { count: 0, cost: 0 };
      branches.forEach((branchName) => {
        const cellData = (brands[brand] && brands[brand][branchName]) || {
          count: 0,
          cost: 0,
        };
        summaryTable += `<td class="text-center">${
          cellData.count || "-"
        } / <br><small class="text-muted">${formatCurrency(
          cellData.cost
        )}</small></td>`;
        brandTotal.count += cellData.count;
        brandTotal.cost += cellData.cost;
        branchTotals.count[branchName] =
          (branchTotals.count[branchName] || 0) + cellData.count;
        branchTotals.cost[branchName] =
          (branchTotals.cost[branchName] || 0) + cellData.cost;
      });
      summaryTable += `<td class="text-center table-primary"><strong>${
        brandTotal.count
      }<br><small>${formatCurrency(
        brandTotal.cost
      )}</small></strong></td></tr>`;
      grandTotalAll.count += brandTotal.count;
      grandTotalAll.cost += brandTotal.cost;
    });
    summaryTable += `<tr class="table-primary"><td><strong>TOTAL</strong></td>`;
    branches.forEach((branchName) => {
      summaryTable += `<td class="text-center"><strong>${
        branchTotals.count[branchName] || 0
      }<br><small>${formatCurrency(
        branchTotals.cost[branchName] || 0
      )}</small></strong></td>`;
    });
    summaryTable += `<td class="text-center"><strong>${
      grandTotalAll.count
    }<br><small>${formatCurrency(
      grandTotalAll.cost
    )}</small></strong></td></tr>`;
    summaryTable += `</tbody></table></div>`;
    tabContentHtml += `<div class="tab-pane fade show active" id="summary-sheet" role="tabpanel">${summaryTable}</div>`;
    brandNames.forEach((brand) => {
      const brandData = models_by_brand[brand] || {};
      const sortedModels = Object.keys(brandData).sort();
      let brandTable = `<div class="table-responsive mt-2"><table class="table table-bordered table-sm table-hover"><thead><tr><th>MODEL</th>`;
      branches.forEach((b) => {
        brandTable += `<th>${escapeHtml(b)}</th>`;
      });
      brandTable += `<th>TOTAL</th></tr></thead><tbody>`;
      const branchSubtotals = { count: {}, cost: {} };
      let brandGrandTotal = { count: 0, cost: 0 };
      sortedModels.forEach((model) => {
        brandTable += `<tr class="model-row" style="cursor: pointer;" onclick="toggleInventorySummaryDetails(this, '${brand}', '${escapeHtml(
          model
        )}', 'all')"><td>${escapeHtml(model)}</td>`;
        let modelTotal = { count: 0, cost: 0 };
        branches.forEach((branchName) => {
          const cellData = (brandData[model] &&
            brandData[model][branchName]) || { count: 0, cost: 0 };
          brandTable += `<td class="text-center">${
            cellData.count || "-"
          } / <br><small class="text-muted">${formatCurrency(
            cellData.cost
          )}</small></td>`;
          modelTotal.count += cellData.count;
          modelTotal.cost += cellData.cost;
          branchSubtotals.count[branchName] =
            (branchSubtotals.count[branchName] || 0) + cellData.count;
          branchSubtotals.cost[branchName] =
            (branchSubtotals.cost[branchName] || 0) + cellData.cost;
        });
        brandTable += `<td class="text-center table-info"><strong>${
          modelTotal.count
        }<br><small>${formatCurrency(
          modelTotal.cost
        )}</small></strong></td></tr>`;
        brandGrandTotal.count += modelTotal.count;
        brandGrandTotal.cost += modelTotal.cost;
      });
      brandTable += `<tr class="table-info"><td><strong>SUBTOTAL</strong></td>`;
      branches.forEach((branchName) => {
        brandTable += `<td class="text-center"><strong>${
          branchSubtotals.count[branchName] || 0
        }<br><small>${formatCurrency(
          branchSubtotals.cost[branchName] || 0
        )}</small></strong></td>`;
      });
      brandTable += `<td class="text-center"><strong>${
        brandGrandTotal.count
      }<br><small>${formatCurrency(
        brandGrandTotal.cost
      )}</small></strong></td></tr>`;
      brandTable += `</tbody></table></div>`;
      tabContentHtml += `<div class="tab-pane fade" id="${brand.toLowerCase()}-sheet" role="tabpanel">${brandTable}</div>`;
    });
    tabContentHtml += `</div>`;
    mainContentHtml += navTabsHtml + tabContentHtml;
  } else {
    
    const branch_name = currentUserBranch;
    let navTabsHtml = `<ul class="nav nav-tabs" role="tablist">
            <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#summary-sheet" type="button">Summary</button></li>`;
    brandNames.forEach((brand) => {
      navTabsHtml += `<li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#${brand.toLowerCase()}-sheet" type="button">${brand}</button></li>`;
    });
    navTabsHtml += `</ul>`;

    let tabContentHtml = `<div class="tab-content border border-top-0 p-3">`;
    let summaryTable = `<h5 class="mt-2">Inventory Summary — ${branch_name}</h5><div class="table-responsive mt-3"><table class="table table-striped table-sm table-bordered">
            <thead><tr class="table-primary"><th>BRAND</th><th class="text-center">TOTAL UNITS</th><th class="text-end">TOTAL INVENTORY COST</th></tr></thead><tbody>`;
    let grandTotalCount = 0;
    let grandTotalCost = 0;
    brandNames.forEach((brand) => {
      const models = data.filter((item) => item.brand === brand);
      const brandTotalCount = models.length;
      const brandTotalCost = models.reduce(
        (sum, item) => sum + parseFloat(item.inventory_cost || 0),
        0
      );
      summaryTable += `<tr><td><strong>${brand}</strong></td><td class="text-center">${brandTotalCount}</td><td class="text-end">${formatCurrency(
        brandTotalCost
      )}</td></tr>`;
      grandTotalCount += brandTotalCount;
      grandTotalCost += brandTotalCost;
    });
    summaryTable += `<tr class="table-dark"><td><strong>GRAND TOTAL</strong></td><td class="text-center"><strong>${grandTotalCount}</strong></td><td class="text-end"><strong>${formatCurrency(
      grandTotalCost
    )}</strong></td></tr>`;
    summaryTable += `</tbody></table></div>`;
    tabContentHtml += `<div class="tab-pane fade show active" id="summary-sheet" role="tabpanel">${summaryTable}</div>`;

    brandNames.forEach((brand) => {
      const models_in_brand = data.filter((item) => item.brand === brand);
      const model_groups = {};
      models_in_brand.forEach((item) => {
        if (!model_groups[item.model])
          model_groups[item.model] = { count: 0, cost: 0 };
        model_groups[item.model].count++;
        model_groups[item.model].cost += parseFloat(item.inventory_cost || 0);
      });
      const sortedModels = Object.keys(model_groups).sort();

      let tableHtml = `<h5 class="mt-2">${brand.toUpperCase()} INVENTORY — ${branch_name}</h5>`;
      if (sortedModels.length === 0) {
        tableHtml += `<div class="alert alert-info mt-3">No ${brand} units found.</div>`;
      } else {
        tableHtml += `<div class="table-responsive mt-3"><table class="table table-hover table-sm"><thead><tr><th>Model</th><th class="text-center">Unit Count</th><th class="text-end">Total Inventory Cost</th></tr></thead><tbody>`;
        let totalCount = 0;
        let totalCost = 0;
        sortedModels.forEach((modelName) => {
          const item = model_groups[modelName];
          tableHtml += `<tr class="model-row" style="cursor: pointer;" onclick="toggleInventorySummaryDetails(this, '${brand}', '${escapeHtml(
            modelName
          )}', '${branch_name}')">
                        <td>${escapeHtml(
                          modelName
                        )}</td><td class="text-center">${
            item.count
          }</td><td class="text-end">${formatCurrency(item.cost)}</td>
                    </tr>`;
          totalCount += item.count;
          totalCost += item.cost;
        });
        tableHtml += `<tr class="table-primary fw-bold"><td>TOTAL</td><td class="text-center">${totalCount}</td><td class="text-end">${formatCurrency(
          totalCost
        )}</td></tr>`;
        tableHtml += `</tbody></table></div>`;
      }
      tabContentHtml += `<div class="tab-pane fade" id="${brand.toLowerCase()}-sheet" role="tabpanel">${tableHtml}</div>`;
    });
    tabContentHtml += `</div>`;
    mainContentHtml += navTabsHtml + tabContentHtml;
  }

  
  const beginningBalance = summary?.beginning_balance || 0;
  const costBeginning = summary?.inventory_cost?.beginning_balance || 0;
  const receivedTransfers = summary?.received_transfers || 0;
  const costReceived = summary?.inventory_cost?.received_transfers || 0;
  const newDeliveries = summary?.new_deliveries || 0;
  const costNewDeliveries = summary?.inventory_cost?.new_deliveries || 0;
  const totalIn = summary?.in || 0;
  const costTotalIn = summary?.inventory_cost?.in || 0;
  const transfersOut = summary?.transfers_out || 0;
  const costTransfersOut = summary?.inventory_cost?.transfers_out || 0;
  const soldDuringMonth = summary?.sold_during_month || 0;
  const costSoldDuringMonth = summary?.inventory_cost?.sold_during_month || 0;
  const totalOut = summary?.out || 0;
  const costTotalOut = summary?.inventory_cost?.out || 0;
  const endingActual = summary?.ending_actual || 0;
  const costEndingActual = summary?.inventory_cost?.ending_actual || 0;

  let summaryCardsHtml = `
        <div class="summary-section" style="position: sticky; top: 20px;">
            <div class="card border-0 shadow-sm mb-3"><div class="card-header bg-transparent pt-3 pb-2"><h6 class="card-title text-center text-muted">BEGINNING BALANCE</h6></div><div class="card-body px-4 pt-0"><div class="d-flex justify-content-between align-items-center pt-2"><div><div class="fw-bold text-muted">Balance Forward</div></div><div class="text-end"><span class="fs-4 fw-bold text-muted">${beginningBalance}</span><div class="small text-muted fw-bold">${formatCurrency(
    costBeginning
  )}</div></div></div></div></div>
            <div class="card border-0 shadow-sm mb-3"><div class="card-header bg-transparent pt-3 pb-2"><h6 class="card-title text-center" style="color: #28a745;">INVENTORY IN</h6></div><div class="card-body px-4 pt-0"><div class="d-flex justify-content-between border-bottom pb-2 mb-2"><small>Received Transfers</small><div class="text-end"><span class="fw-bold text-success">${receivedTransfers}</span><div class="small text-muted">${formatCurrency(
    costReceived
  )}</div></div></div><div class="d-flex justify-content-between border-bottom pb-2 mb-2"><small>New Deliveries</small><div class="text-end"><span class="fw-bold text-success">${newDeliveries}</span><div class="small text-muted">${formatCurrency(
    costNewDeliveries
  )}</div></div></div><div class="d-flex justify-content-between pt-2"><strong class="text-success">TOTAL IN</strong><div class="text-end"><strong class="fs-5 text-success">${totalIn}</strong><div class="small text-success fw-bold">${formatCurrency(
    costTotalIn
  )}</div></div></div></div></div>
            <div class="card border-0 shadow-sm mb-3"><div class="card-header bg-transparent pt-3 pb-2"><h6 class="card-title text-center" style="color: #dc3545;">INVENTORY OUT</h6></div><div class="card-body px-4 pt-0"><div class="d-flex justify-content-between border-bottom pb-2 mb-2"><small>Transfers Out</small><div class="text-end"><span class="fw-bold text-danger">${transfersOut}</span><div class="small text-muted">${formatCurrency(
    costTransfersOut
  )}</div></div></div><div class="d-flex justify-content-between border-bottom pb-2 mb-2"><small>Sold Units</small><div class="text-end"><span class="fw-bold text-danger">${soldDuringMonth}</span><div class="small text-muted">${formatCurrency(
    costSoldDuringMonth
  )}</div></div></div><div class="d-flex justify-content-between pt-2"><strong class="text-danger">TOTAL OUT</strong><div class="text-end"><strong class="fs-5 text-danger">${totalOut}</strong><div class="small text-danger fw-bold">${formatCurrency(
    costTotalOut
  )}</div></div></div></div></div>
            <div class="card border-0 shadow-sm mb-3"><div class="card-header bg-transparent pt-3 pb-2"><h6 class="card-title text-center" style="color: #000f71;">ENDING BALANCE</h6></div><div class="card-body px-4 pt-0"><div class="d-flex justify-content-between align-items-center pt-2"><div><div class="fw-bold" style="color: #000f71;">Actual</div></div><div class="text-end"><span class="fs-4 fw-bold" style="color: #000f71;">${endingActual}</span><div class="small text-black fw-bold">${formatCurrency(
    costEndingActual
  )}</div></div></div></div></div>
        </div>`;

  mainContentHtml += generateBrandSummaryHtml(data);
  
  const finalHtml = `<div class="row"><div class="col-lg-8">${mainContentHtml}</div><div class="col-lg-4">${summaryCardsHtml}</div></div>`;
  $("#monthlyReportContent").html(finalHtml);
}
function getBranchShortcut(branchName) {
  const shortcuts = {
    HEADOFFICE: "HO",
    KINGDOM: "KDM",
    TANQUE: "TNQ",
    DFISHER: "DFS",
    "ROXAS SUZUKI": "RXS-S",
    "ROXAS HONDA": "RXS-H",
    MAMBUSAO: "MAM",
    SIGMA: "SGM",
    PRC: "PRC",
    BAILAN: "BLN",
    CUARTERO: "CTO",
    JAMINDAN: "JAM",
    "ANTIQUE-1": "ANT-1",
    "ANTIQUE-2": "ANT-2",
    "DELGADO HONDA": "SDH",
    "DELGADO SUZUKI": "SDS",
    "JARO-1": "JAR-1",
    "JARO-2": "JAR-2",
    "KALIBO MABINI": "SKM",
    "KALIBO SUZUKI": "SKS",
    ALTAVAS: "ALT",
    EMAP: "EMP",
    CULASI: "CUL",
    BACOLOD: "BAC",
    "PASSI-1": "PAS-1",
    "PASSI-2": "PAS-2",
    BALASAN: "BAL",
    GUIMARAS: "GUI",
    "PEMDI BACOLOD": "PEMDI",
    "EEMSI-GUIMARAS": "EEMSI",
    "INFINITY BACOLOD": "INF",
    AJUY: "AJY",
    "MINDORO ROXAS": "MDR",
    "3S MINDORO": "M3S",
    "MINDORO-MB": "MB",
    "MINDORO MANSALAY": "MAN",
    "K-RIDERS ROXAS": "K-RID",
    IBAJAY: "IBA",
    NUMANCIA: "NUM",
    CFCIPRC: "CFC",
  };
  return shortcuts[branchName.toUpperCase()] || branchName;
}

function generateInventorySummaryReportPDF() {
  const { jsPDF } = window.jspdf;
  if (!currentReportData || currentReportType !== "inventory_summary") {
    showErrorModal("Please generate an inventory summary report first.");
    return;
  }

  const { data } = currentReportData;
  const report_scope = isHeadOffice || isAdminUser ? "global" : "branch";

  
  let dateSubtitle = "";
  let fileNameDate = new Date().toISOString().slice(0, 10);
  if (currentReportDate) {
    dateSubtitle = `As of ${formatDate(currentReportDate)}`;
    fileNameDate = currentReportDate;
  } else if (currentReportMonth) {
    const [year, monthNum] = currentReportMonth.split("-");
    const monthName = new Date(year, monthNum - 1, 1).toLocaleString(
      "default",
      { month: "long" }
    );
    dateSubtitle = `For the Month of ${monthName} ${year}`;
    fileNameDate = currentReportMonth;
  }

  
  const headerBlue = [0, 15, 113];
  const subheaderGray = [73, 80, 87];
  const footerGray = [108, 117, 125];
  const tableHeaderBlue = [41, 128, 185];
  const totalBlueLight = [231, 245, 255];
  const grandTotalBlue = [204, 232, 255];
  const tableHeadStyles = {
    fillColor: tableHeaderBlue,
    textColor: [255, 255, 255],
    fontStyle: "bold",
    halign: "center",
    valign: "middle",
    fontSize: 7, 
    cellPadding: 2, 
  };

  
  const drawHeader = (doc, title, subtitle, filters = []) => {
    const pageWidth = doc.internal.pageSize.getWidth();
    let currentY = 25;
    doc
      .setFont("helvetica", "bold")
      .setFontSize(16)
      .setTextColor(...headerBlue);
    doc.text("SOLID MOTORCYCLE DISTRIBUTORS, INC.", pageWidth / 2, currentY, {
      align: "center",
    });
    currentY += 15;
    doc.setFontSize(14).setTextColor(...subheaderGray);
    doc.text(title, pageWidth / 2, currentY, { align: "center" });
    currentY += 12;
    doc.setFontSize(11).setTextColor(...subheaderGray);
    doc.text(subtitle, pageWidth / 2, currentY, { align: "center" });

    
    if (filters.length > 0) {
      currentY += 10;
      doc.setFontSize(9).setTextColor(...subheaderGray);
      filters.forEach((filter) => {
        doc.text(filter, pageWidth / 2, currentY, { align: "center" });
        currentY += 8;
      });
    }

    return currentY + 15;
  };

  const addFooters = (doc, reportTitle) => {
    const pageCount = doc.internal.getNumberOfPages();
    const genTime = new Date().toLocaleString("en-US", {
      dateStyle: "full",
      timeStyle: "short",
    });
    for (let i = 1; i <= pageCount; i++) {
      doc.setPage(i);
      doc.setFontSize(8).setTextColor(...footerGray);
      doc.text(
        `Generated on: ${genTime}`,
        40,
        doc.internal.pageSize.getHeight() - 20
      );
      doc.text(
        `Page ${i} of ${pageCount} | ${reportTitle}`,
        doc.internal.pageSize.getWidth() / 2,
        doc.internal.pageSize.getHeight() - 20,
        { align: "center" }
      );
    }
  };

  if (report_scope === "global") {
    const doc = new jsPDF({
      orientation: "landscape",
      unit: "pt",
      format: "legal",
    });

    const branches = [
      ...new Set(data.map((item) => item.current_branch)),
    ].sort();
    const branchShortcuts = branches.map((b) => getBranchShortcut(b));
    const brandNames = ["Suzuki", "Honda", "Yamaha", "Kawasaki"];
    const brands = {};
    const models_by_brand = {};
    brandNames.forEach((b) => {
      brands[b] = {};
      models_by_brand[b] = {};
    });
    for (const item of data) {
      const { brand, model, current_branch } = item;
      if (brandNames.includes(brand)) {
        if (!brands[brand][current_branch]) brands[brand][current_branch] = 0;
        brands[brand][current_branch]++;
        if (!models_by_brand[brand][model]) models_by_brand[brand][model] = {};
        if (!models_by_brand[brand][model][current_branch])
          models_by_brand[brand][model][current_branch] = 0;
        models_by_brand[brand][model][current_branch]++;
      }
    }

    
    let startY = drawHeader(doc, "Inventory Summary Report", dateSubtitle);

    
    const pageWidth = doc.internal.pageSize.getWidth();
    const margin = 40;
    const availableWidth = pageWidth - 2 * margin;
    const brandColWidth = 80; 
    const branchColWidth = Math.min(
      35,
      (availableWidth - brandColWidth - 60) / branches.length
    ); 
    const totalColWidth = 50; 

    const summaryHead = [["BRAND", ...branchShortcuts, "GRAND TOTAL"]];
    const summaryBody = [];
    const branchTotals = {};
    let grandTotalAll = 0;

    brandNames.forEach((brand) => {
      const rowData = [
        { content: `${brand} SUB-TOTAL`, styles: { fontStyle: "bold" } },
      ];
      let brandTotal = 0;
      branches.forEach((branchName) => {
        const count = (brands[brand] && brands[brand][branchName]) || 0;
        rowData.push(count || "");
        brandTotal += count;
        branchTotals[branchName] = (branchTotals[branchName] || 0) + count;
      });
      rowData.push(brandTotal);
      summaryBody.push(rowData);
      grandTotalAll += brandTotal;
    });

    const totalRow = [
      { content: "GRAND TOTAL", styles: { fontStyle: "bold" } },
    ];
    branches.forEach((branchName) => {
      totalRow.push(branchTotals[branchName] || "");
    });
    totalRow.push(grandTotalAll);
    summaryBody.push(totalRow);

    
    const columnStyles = {
      0: {
        halign: "left",
        cellWidth: brandColWidth,
        fontSize: 8,
      },
    };

    
    branches.forEach((_, index) => {
      columnStyles[index + 1] = {
        halign: "center",
        cellWidth: branchColWidth,
        fontSize: 7, 
        cellPadding: 1, 
      };
    });

    
    columnStyles[branches.length + 1] = {
      halign: "center",
      cellWidth: totalColWidth,
      fontSize: 8,
      fontStyle: "bold",
    };

    doc.autoTable({
      head: summaryHead,
      body: summaryBody,
      startY: startY,
      theme: "grid",
      headStyles: tableHeadStyles,
      bodyStyles: {
        fontSize: 8,
        halign: "center",
        cellPadding: 2,
      },
      columnStyles: columnStyles,
      didParseCell: (data) => {
        if (data.cell.section === "body") {
          const cellContent = data.row.raw[0]?.content || data.row.raw[0];
          if (typeof cellContent === "string") {
            if (cellContent.includes("SUB-TOTAL")) {
              data.cell.styles.fillColor = totalBlueLight;
              data.cell.styles.fontStyle = "bold";
            }
            if (cellContent === "GRAND TOTAL") {
              data.cell.styles.fillColor = grandTotalBlue;
              data.cell.styles.fontStyle = "bold";
            }
          }
        }
      },
    });

    
    brandNames.forEach((brand) => {
      const brandData = models_by_brand[brand] || {};
      const sortedModels = Object.keys(brandData);
      if (sortedModels.length > 0) {
        doc.addPage();
        startY = drawHeader(
          doc,
          `${brand.toUpperCase()} INVENTORY DETAILS`,
          dateSubtitle
        );

        
        const modelColWidth = 100; 
        const detailBranchColWidth = Math.min(
          30,
          (availableWidth - modelColWidth - 40) / branches.length
        ); 
        const modelTotalColWidth = 40;

        const head = [["MODEL", ...branchShortcuts, "TOTAL"]];
        const body = [];
        const branchSubtotals = {};
        let brandGrandTotal = 0;

        sortedModels.sort().forEach((model) => {
          const row = [model];
          let modelTotal = 0;
          branches.forEach((branchName) => {
            const count =
              (brandData[model] && brandData[model][branchName]) || 0;
            row.push(count || "");
            modelTotal += count;
            branchSubtotals[branchName] =
              (branchSubtotals[branchName] || 0) + count;
          });
          row.push(modelTotal);
          body.push(row);
          brandGrandTotal += modelTotal;
        });

        const subtotalRow = [
          { content: "SUBTOTAL", styles: { fontStyle: "bold" } },
        ];
        branches.forEach((branchName) => {
          subtotalRow.push(branchSubtotals[branchName] || "");
        });
        subtotalRow.push(brandGrandTotal);
        body.push(subtotalRow);

        
        const brandDetailColumnStyles = {
          0: {
            halign: "left",
            cellWidth: modelColWidth,
            fontSize: 6, 
            cellPadding: 1,
          },
        };

        
        branches.forEach((_, index) => {
          brandDetailColumnStyles[index + 1] = {
            halign: "center",
            cellWidth: detailBranchColWidth,
            fontSize: 5, 
            cellPadding: 1,
          };
        });

        
        brandDetailColumnStyles[branches.length + 1] = {
          halign: "center",
          cellWidth: modelTotalColWidth,
          fontSize: 6,
          fontStyle: "bold",
          cellPadding: 1,
        };

        doc.autoTable({
          head: head,
          body: body,
          startY: startY,
          theme: "grid",
          headStyles: tableHeadStyles,
          bodyStyles: {
            fontSize: 5, 
            halign: "center",
            cellPadding: 1, 
          },
          columnStyles: brandDetailColumnStyles,
          didParseCell: (data) => {
            if (data.cell.section === "body") {
              const cellContent = data.row.raw[0]?.content || data.row.raw[0];
              if (
                typeof cellContent === "string" &&
                cellContent === "SUBTOTAL"
              ) {
                data.cell.styles.fillColor = totalBlueLight;
                data.cell.styles.fontStyle = "bold";
              }
            }
          },
          
          willDrawCell: (data) => {
            if (data.section === "body" && data.column.index === 0) {
              data.cell.text = data.cell.text.join(" ");
            }
          },
        });
      }
    });
    let lastY = doc.autoTable.previous.finalY;
    addBrandSummaryToPdf(doc, data, lastY);
    addFooters(doc, "Global Inventory Tally");
    doc.save(`Global_Inventory_Tally_${fileNameDate}.pdf`);
  } else {
    
    const branch_name = currentUserBranch;
    const doc = new jsPDF({
      orientation: "landscape",
      unit: "pt",
      format: "letter",
    });
    const brandNames = ["Suzuki", "Honda", "Yamaha", "Kawasaki"];

    let startY = drawHeader(doc, "Inventory Summary Report", dateSubtitle, [
      `Branch: ${branch_name}`,
    ]);
    const summaryHead = [["BRAND", "TOTAL UNITS", "TOTAL INVENTORY COST"]];
    const summaryBody = [];
    let grandTotalCount = 0,
      grandTotalCost = 0;

    brandNames.forEach((brand) => {
      const models = data.filter((item) => item.brand === brand);
      const brandTotalCount = models.length;
      const brandTotalCost = models.reduce(
        (sum, item) => sum + parseFloat(item.inventory_cost || 0),
        0
      );
      summaryBody.push([
        `${brand} SUB-TOTAL`,
        brandTotalCount,
        formatCurrency(brandTotalCost),
      ]);
      grandTotalCount += brandTotalCount;
      grandTotalCost += brandTotalCost;
    });
    summaryBody.push([
      { content: "GRAND TOTAL", styles: { fontStyle: "bold" } },
      { content: grandTotalCount, styles: { fontStyle: "bold" } },
      {
        content: formatCurrency(grandTotalCost),
        styles: { fontStyle: "bold" },
      },
    ]);

    doc.autoTable({
      head: summaryHead,
      body: summaryBody,
      startY: startY,
      theme: "grid",
      headStyles: tableHeadStyles,
      styles: { fontSize: 9 },
      columnStyles: {
        0: { fontStyle: "bold" },
        1: { halign: "center" },
        2: { halign: "right" },
      },
      didParseCell: (data) => {
        if (data.cell.section === "body") {
          const cellContent = data.row.raw[0]?.content || data.row.raw[0];
          if (typeof cellContent === "string") {
            if (cellContent.includes("SUB-TOTAL"))
              data.cell.styles.fillColor = totalBlueLight;
            if (cellContent === "GRAND TOTAL")
              data.cell.styles.fillColor = grandTotalBlue;
          }
        }
      },
    });

    brandNames.forEach((brand) => {
      const models_in_brand = data.filter((item) => item.brand === brand);
      if (models_in_brand.length > 0) {
        doc.addPage();
        startY = drawHeader(
          doc,
          `${brand.toUpperCase()} INVENTORY DETAILS`,
          dateSubtitle,
          [`Branch: ${branch_name}`]
        );
        const model_groups = {};
        models_in_brand.forEach((item) => {
          if (!model_groups[item.model])
            model_groups[item.model] = { count: 0, cost: 0 };
          model_groups[item.model].count++;
          model_groups[item.model].cost += parseFloat(item.inventory_cost || 0);
        });
        const sortedModels = Object.keys(model_groups).sort();
        const detailsHead = [["Model", "Unit Count", "Total Inventory Cost"]];
        const detailsBody = [];
        let totalCount = 0,
          totalCost = 0;
        sortedModels.forEach((modelName) => {
          const item = model_groups[modelName];
          detailsBody.push([modelName, item.count, formatCurrency(item.cost)]);
          totalCount += item.count;
          totalCost += item.cost;
        });
        detailsBody.push([
          { content: "TOTAL", styles: { fontStyle: "bold" } },
          { content: totalCount, styles: { fontStyle: "bold" } },
          { content: formatCurrency(totalCost), styles: { fontStyle: "bold" } },
        ]);
        doc.autoTable({
          head: detailsHead,
          body: detailsBody,
          startY: startY,
          theme: "grid",
          headStyles: tableHeadStyles,
          styles: { fontSize: 9 },
          columnStyles: { 1: { halign: "center" }, 2: { halign: "right" } },
          didParseCell: (data) => {
            if (data.cell.section === "body") {
              const cellContent = data.row.raw[0]?.content || data.row.raw[0];
              if (typeof cellContent === "string" && cellContent === "TOTAL") {
                data.cell.styles.fillColor = totalBlueLight;
              }
            }
          },
        });
      }
    });
    let lastY_branch = doc.autoTable.previous.finalY;
    addBrandSummaryToPdf(doc, data, lastY_branch);
    addFooters(doc, `Branch Inventory Summary (${branch_name})`);
    doc.save(`Branch_Inventory_Summary_${branch_name}_${fileNameDate}.pdf`);
  }
}


function renderSoldUnitsReport(response) {
  const { data, summary, branch, month, date, start_date, end_date } = response;
  let dateSubtitle = "";

  if (date) {
    dateSubtitle = `For ${formatDate(date)}`;
  } else if (month && month.includes("-")) {
    const [year, monthNum] = month.split("-");
    const monthName = new Date(year, monthNum - 1, 1).toLocaleString(
      "default",
      { month: "long" }
    );
    dateSubtitle = `For the Month of ${monthName} ${year}`;
  } else if (start_date && end_date) {
    if (start_date === end_date) {
      dateSubtitle = `For ${formatDate(start_date)}`;
    } else {
      dateSubtitle = `From ${formatDate(start_date)} to ${formatDate(
        end_date
      )}`;
    }
  }

  const branchDisplay =
    currentReportBranch && currentReportBranch.toLowerCase() !== "all"
      ? currentReportBranch
      : "ALL BRANCHES";
  const saleTypeDisplay =
    currentReportSaleType && currentReportSaleType.toLowerCase() !== "all"
      ? currentReportSaleType
      : "ALL TYPES OF SALE";

  $("#monthlyInventoryReportModalLabel").text("Summary of Sold Units Report");

  if (!data || data.length === 0) {
    $("#monthlyReportContent").html(`
      <div class="report-header text-center mb-4">
        <h5 class="mb-2" style="color: #495057; font-weight: 500;">Summary of Sold Units Report</h5>
        <h6 class="mb-2 text-muted" style="font-weight: 400;">${dateSubtitle}</h6>
      </div>
      <div class="alert alert-info text-center my-3">No sold unit/s found for the selected filters.</div>
    `);
    return;
  }

  const branches = {};
  data.forEach((item) => {
    const b = item.current_branch || "Unknown Branch";
    if (!branches[b]) branches[b] = [];
    branches[b].push(item);
  });

  let tablesHtml = "";
  Object.keys(branches)
    .sort()
    .forEach((branchName) => {
      const branchData = branches[branchName];
      const codSales = branchData.filter((i) => i.payment_type === "COD");
      const installmentSales = branchData.filter(
        (i) => i.payment_type === "Installment"
      );

      const buildTableHtml = (title, salesData) => {
        if (!salesData.length)
          return `<h6 class="mt-3">${title}</h6><p class="text-muted small">No ${title.toLowerCase()} found for this branch.</p>`;
        let rowsHtml = "";
        salesData.forEach((item) => {
          const details =
            item.payment_type === "COD"
              ? `DR#: ${escapeHtml(
                  item.dr_number || "N/A"
                )}, Amount: ${formatCurrency(item.cod_amount)}`
              : `Terms: ${escapeHtml(
                  item.terms || "N/A"
                )}, Monthly: ${formatCurrency(item.monthly_amortization)}`;
          rowsHtml += `
          <tr>
            <td>${formatDate(item.sale_date)}</td>
            <td>${escapeHtml(item.customer_name)}</td>
            <td>${escapeHtml(item.model)}</td>
            <td><code>${escapeHtml(item.engine_number)}</code></td>
            <td><code>${escapeHtml(item.frame_number)}</code></td>
            <td class="text-center">${escapeHtml(item.payment_type)}</td>
            <td>${details}</td>
          </tr>
        `;
        });
        return `
        <h6 class="mt-3">${title} (${salesData.length})</h6>
        <div class="table-responsive">
          <table class="table table-sm table-striped table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th style="width: 10%;">Date</th>
                <th>Customer</th>
                <th>Model</th>
                <th>Engine #</th>
                <th>Frame #</th>
                <th class="text-center" style="width: 10%;">Type</th>
                <th>Details</th>
              </tr>
            </thead>
            <tbody>${rowsHtml}</tbody>
          </table>
        </div>
      `;
      };

      tablesHtml += `
      <div class="card mb-4">
        <div class="card-header bg-light"><h6 class="mb-0">${branchName} - ${
        branchData.length
      } total unit/s sold</h6></div>
        <div class="card-body p-3">
          ${buildTableHtml("COD Sales", codSales)}
          ${buildTableHtml("Installment Sales", installmentSales)}
        </div>
      </div>
    `;
    });

  const totalCod = data.filter((i) => i.payment_type === "COD").length;
  const totalInstallment = data.filter(
    (i) => i.payment_type === "Installment"
  ).length;
  const totalSales = data.length;

  let html = `
    <div class="report-header text-center mb-4">
      <div class="d-flex align-items-center justify-content-center mb-2">
        <div style="width: 40px; height: 2px; background: #000f71; margin-right: 15px;"></div>
        <h4 class="mb-0" style="color: #000f71; font-weight: 600; letter-spacing: 0.5px;">SOLID MOTORCYCLE DISTRIBUTORS, INC.</h4>
        <div style="width: 40px; height: 2px; background: #000f71; margin-left: 15px;"></div>
      </div>
      <h5 class="mb-2" style="color: #495057; font-weight: 500;">Summary of Sold Units Report</h5>
      <h6 class="mb-2 text-muted" style="font-weight: 400;">${dateSubtitle}</h6>
     ${buildFilterDisplayHtml()}
    </div>

    <div class="row mb-4">
      <div class="col-md-4 mb-3 mb-md-0">
        <div class="card border-0 shadow-sm text-center h-100">
          <div class="card-body py-3"><h6 class="card-title mb-1 text-muted small">TOTAL COD SALES</h6><h3 class="mb-0">${totalCod}</h3></div>
        </div>
      </div>
      <div class="col-md-4 mb-3 mb-md-0">
        <div class="card border-0 shadow-sm text-center h-100">
          <div class="card-body py-3"><h6 class="card-title mb-1 text-muted small">TOTAL INSTALLMENT SALES</h6><h3 class="mb-0">${totalInstallment}</h3></div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card border-primary shadow-sm text-center h-100">
          <div class="card-body py-3"><h6 class="card-title mb-1 text-muted small">GRAND TOTAL SOLD</h6><h3 class="mb-0">${totalSales}</h3></div>
        </div>
      </div>
    </div>

    ${tablesHtml}
    ${generateBrandSummaryHtml(data)}
  `;

  $("#monthlyReportContent").html(html);
}

function generateSoldUnitsReportPDF() {
  const { jsPDF } = window.jspdf;

  if (!currentReportData || currentReportType !== "sold_units") {
    showErrorModal(
      "Please generate a sold units report first before exporting to PDF."
    );
    return;
  }

  const doc = new jsPDF({ orientation: "landscape", unit: "mm", format: "a4" });

  let dateSubtitle = "";
  let fileNameDate = new Date().toISOString().slice(0, 10);

  if (currentReportDate) {
    dateSubtitle = `For ${formatDate(currentReportDate)}`;
    fileNameDate = currentReportDate;
  } else if (currentReportMonth && currentReportMonth.includes("-")) {
    const [year, monthNum] = currentReportMonth.split("-");
    const monthName = new Date(year, monthNum - 1, 1).toLocaleString(
      "default",
      { month: "long" }
    );
    dateSubtitle = `For the Month of ${monthName} ${year}`;
    fileNameDate = currentReportMonth;
  } else if (currentReportStartDate && currentReportEndDate) {
    if (currentReportStartDate === currentReportEndDate) {
      dateSubtitle = `For ${formatDate(currentReportStartDate)}`;
      fileNameDate = currentReportStartDate;
    } else {
      dateSubtitle = `From ${formatDate(
        currentReportStartDate
      )} to ${formatDate(currentReportEndDate)}`;
      fileNameDate = `${currentReportStartDate}_to_${currentReportEndDate}`;
    }
  }

  const pageWidth = doc.internal.pageSize.getWidth();
  const pageHeight = doc.internal.pageSize.getHeight();
  const marginLR = 10;
  const marginBottom = 15;
  let currentY = 15;

  
  doc.setFont("helvetica", "bold").setFontSize(13).setTextColor(0, 15, 113);
  doc.text("SOLID MOTORCYCLE DISTRIBUTORS, INC.", pageWidth / 2, currentY, {
    align: "center",
  });
  currentY += 9;
  doc.setFontSize(11).setTextColor(73, 80, 87);
  doc.text("Summary of Sold Units Report", pageWidth / 2, currentY, {
    align: "center",
  });
  currentY += 6;
  doc.setFontSize(10).setTextColor(0, 64, 133);
  doc.text(dateSubtitle, pageWidth / 2, currentY, { align: "center" });
  currentY += 6;

  currentY = addFiltersToPdf(doc, currentY);

  
  const groupedData = {};
  currentReportData.data.forEach((item) => {
    const branchName = item.current_branch || "Unknown Branch";
    if (!groupedData[branchName]) groupedData[branchName] = [];
    groupedData[branchName].push(item);
  });

  const columns = [
    { header: "Date", dataKey: "sale_date" },
    { header: "Customer Name", dataKey: "customer_name" },
    { header: "Model", dataKey: "model" },
    { header: "Engine #", dataKey: "engine_number" },
    { header: "Frame #", dataKey: "frame_number" },
    { header: "Type", dataKey: "payment_type" },
    { header: "Details", dataKey: "details" },
  ];

  const formatRows = (items) =>
    items.map((item) => {
      const details =
        item.payment_type === "COD"
          ? `DR#: ${escapeHtml(
              item.dr_number || "N/A"
            )}, Amount: ${formatCurrency(item.cod_amount)}`
          : `Terms: ${escapeHtml(
              item.terms || "N/A"
            )}, Monthly: ${formatCurrency(item.monthly_amortization)}`;
      return {
        sale_date: formatDate(item.sale_date),
        customer_name: escapeHtml(item.customer_name),
        model: escapeHtml(item.model),
        engine_number: escapeHtml(item.engine_number),
        frame_number: escapeHtml(item.frame_number),
        payment_type: escapeHtml(item.payment_type),
        details: details,
      };
    });

  for (const branchName in groupedData) {
    const items = groupedData[branchName];
    const codItems = items.filter((i) => i.payment_type === "COD");
    const installmentItems = items.filter(
      (i) => i.payment_type === "Installment"
    );

    if (currentY + 20 > pageHeight - marginBottom) {
      doc.addPage();
      currentY = 20;
    }

    doc.setFontSize(10).setTextColor(0, 64, 133).setFont("helvetica", "bold");
    doc.text(`${branchName} - ${items.length} unit/s sold`, marginLR, currentY);
    currentY += 5;

    if (codItems.length > 0) {
      doc.setFontSize(9).setTextColor(0, 15, 113).setFont("helvetica", "bold");
      doc.text("COD Sales", marginLR, currentY);
      currentY += 4;
      doc.autoTable({
        startY: currentY,
        margin: { left: marginLR, right: marginLR },
        head: [columns.map((c) => c.header)],
        body: formatRows(codItems).map((r) => columns.map((c) => r[c.dataKey])),
        styles: {
          fontSize: 7,
          cellPadding: 1.5,
          valign: "middle",
          overflow: "linebreak",
        },
        headStyles: {
          fillColor: [248, 249, 250],
          textColor: [73, 80, 87],
          fontStyle: "bold",
          halign: "center",
        },
        theme: "striped",
        didDrawPage: (data) => {
          currentY = data.cursor.y;
        },
      });
      currentY = doc.autoTable.previous.finalY + 7;
    }

    if (installmentItems.length > 0) {
      if (currentY + 15 > pageHeight - marginBottom) {
        doc.addPage();
        currentY = 20;
      }
      doc.setFontSize(9).setTextColor(0, 15, 113).setFont("helvetica", "bold");
      doc.text("Installment Sales", marginLR, currentY);
      currentY += 4;
      doc.autoTable({
        startY: currentY,
        margin: { left: marginLR, right: marginLR },
        head: [columns.map((c) => c.header)],
        body: formatRows(installmentItems).map((r) =>
          columns.map((c) => r[c.dataKey])
        ),
        styles: {
          fontSize: 7,
          cellPadding: 1.5,
          valign: "middle",
          overflow: "linebreak",
        },
        headStyles: {
          fillColor: [248, 249, 250],
          textColor: [73, 80, 87],
          fontStyle: "bold",
          halign: "center",
        },
        theme: "striped",
        didDrawPage: (data) => {
          currentY = data.cursor.y;
        },
      });
      currentY = doc.autoTable.previous.finalY + 10;
    }
  }

  
  const totalCod = currentReportData.data.filter(
    (i) => i.payment_type === "COD"
  ).length;
  const totalInstallment = currentReportData.data.filter(
    (i) => i.payment_type === "Installment"
  ).length;
  const totalCombined = totalCod + totalInstallment;
  const cardWidth = (pageWidth - 2 * marginLR - 20) / 3;
  const cardHeight = 20;

  if (currentY + cardHeight > pageHeight - marginBottom) {
    doc.addPage();
    currentY = 20;
  }

  function drawCard(x, y, title, value) {
    doc.setDrawColor(222, 226, 230);
    doc.setFillColor(248, 249, 250);
    doc.roundedRect(x, y, cardWidth, cardHeight, 3, 3, "FD");
    doc.setFontSize(8).setTextColor(108, 117, 125).setFont("helvetica", "bold");
    doc.text(title, x + cardWidth / 2, y + 7, { align: "center" });
    doc.setFontSize(12).setTextColor(0, 0, 0).setFont("helvetica", "bold");
    doc.text(String(value), x + cardWidth / 2, y + 15, { align: "center" });
  }

  drawCard(marginLR, currentY, "TOTAL SOLD (COD)", totalCod);
  drawCard(
    marginLR + cardWidth + 10,
    currentY,
    "TOTAL SOLD (INSTALLMENT)",
    totalInstallment
  );
  drawCard(
    marginLR + 2 * (cardWidth + 10),
    currentY,
    "GRAND TOTAL SOLD",
    totalCombined
  );

  currentY += cardHeight;
  currentY = addBrandSummaryToPdf(doc, currentReportData.data, currentY);

  
  const generatedOn = new Date().toLocaleDateString("en-US", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  });
  const totalPages = doc.internal.getNumberOfPages();
  for (let i = 1; i <= totalPages; i++) {
    doc.setPage(i);
    doc.setFontSize(8).setTextColor(108, 117, 125);
    doc.text(`Generated on: ${generatedOn}`, 10, pageHeight - 10);
    doc.text(`Page ${i} of ${totalPages}`, pageWidth / 2, pageHeight - 10, {
      align: "center",
    });
  }

  const safeBranch = (currentReportBranch || "ALL").replace(/\s+/g, "_");
  const safeSaleType = (currentReportSaleType || "ALL").replace(/\s+/g, "_");
  doc.save(
    `Sold_Units_Report_${fileNameDate}_${safeBranch}_${safeSaleType}.pdf`
  );
}



function generateTransferredSummary(
  month,
  branch,
  category = "all",
  brand = "all"
) {
  $("#monthlyReportContent").html(
    '<div class="text-center py-5"><div class="spinner-border text-black" role="status"></div></div>'
  );

  $.ajax({
    url: "../api/inventory_management.php",
    method: "GET",
    data: {
      action: "get_monthly_transferred_summary",
      month: month,
      branch: branch,
      category: category,
      brand: brand,
    },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        currentReportData = response.data;
        currentReportMonth = response.month;
        currentReportBranch = response.branch;
        currentReportType = "transferred";
        currentReportSummary = response.summary;

        renderTransferredSummaryReport(
          response.data,
          response.month,
          response.branch,
          response.summary
        );
        $("#monthlyInventoryReportModal").modal("show");
      } else {
        showErrorModal(
          response.message || "Error generating transferred summary"
        );
      }
    },
    error: function (xhr, status, error) {
      showErrorModal("Error generating transferred summary: " + error);
    },
  });
}

function renderTransferredSummaryReport(response) {
  const { data, summary, branch, month, date, start_date, end_date } = response;

  let dateSubtitle = "";
  if (date) {
    dateSubtitle = `For ${formatDate(date)}`;
  } else if (month && month.includes("-")) {
    const [year, monthNum] = month.split("-");
    const monthName = new Date(year, monthNum - 1, 1).toLocaleString(
      "default",
      { month: "long" }
    );
    dateSubtitle = `For the Month of ${monthName} ${year}`;
  } else if (start_date && end_date) {
    if (start_date === end_date) {
      dateSubtitle = `For ${formatDate(start_date)}`;
    } else {
      dateSubtitle = `From ${formatDate(start_date)} to ${formatDate(
        end_date
      )}`;
    }
  }

  const totalTransferred = summary?.total_transferred || 0;
  const totalInventoryCost = summary?.total_inventory_cost || 0;

  let tableHtml = "";
  if (!data || data.length === 0) {
    tableHtml = `
      <div class="alert alert-info text-center mt-4">
        <i class="bi bi-info-circle me-2"></i>
        No transfers found for the selected period from ${branch} branch.
      </div>
    `;
  } else {
    tableHtml = `
      <div class="table-responsive">
        <table class="table table-striped table-hover">
          <thead class="table-dark" style="position: sticky; top: 0; z-index: 10;">
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
      tableHtml += `
        <tr>
          <td>${index + 1}</td>
          <td>${escapeHtml(item.invoice_number || "N/A")}</td>
          <td>${escapeHtml(item.model)}</td>
          <td class="fw-bold">${escapeHtml(item.brand)}</td>
          <td>${escapeHtml(item.color)}</td>
          <td><code>${escapeHtml(item.engine_number)}</code></td>
          <td><code>${escapeHtml(item.frame_number)}</code></td>
          <td>${formatDate(item.transfer_date)}</td>
          <td><span class="badge bg-info">${escapeHtml(
            item.transferred_to
          )}</span></td>
          <td class="text-end fw-bold">${formatCurrency(
            item.inventory_cost
          )}</td>
        </tr>
      `;
    });
    tableHtml += `
          </tbody>
          <tfoot class="table-group-divider">
            <tr class="table-active">
              <td colspan="9" class="text-end fw-bold">Total:</td>
              <td class="text-end fw-bold">${formatCurrency(
                totalInventoryCost
              )}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    `;
  }

  let html = `
    <div class="report-header text-center mb-4">
      <div class="d-flex align-items-center justify-content-center mb-2">
        <div style="width: 40px; height: 2px; background: #000f71; margin-right: 15px;"></div>
        <h4 class="mb-0" style="color: #000f71; font-weight: 600; letter-spacing: 0.5px;">SOLID MOTORCYCLE DISTRIBUTORS, INC.</h4>
        <div style="width: 40px; height: 2px; background: #000f71; margin-left: 15px;"></div>
      </div>
      <h5 class="mb-2" style="color: #495057; font-weight: 500;">SUMMARY OF TRANSFERRED STOCKS</h5>
      <h6 class="mb-2 text-muted" style="font-weight: 400;">${dateSubtitle}</h6>
    ${buildFilterDisplayHtml()}
    </div>

    <div class="row mb-4">
      <div class="col-md-6 mb-3 mb-md-0">
        <div class="card border-0 shadow-sm text-center h-100" style="background: linear-gradient(135deg, #000f71, #1a237e); color: white;">
          <div class="card-body py-3">
            <h6 class="card-title mb-1 text-white-50" style="font-size: 0.9rem;">TOTAL TRANSFERRED</h6>
            <h3 class="mb-0 text-white">${totalTransferred}</h3>
            <small class="text-white-50">Motorcycles</small>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card border-0 shadow-sm text-center h-100" style="background: linear-gradient(135deg, #28a745, #20c997); color: white;">
          <div class="card-body py-3">
            <h6 class="card-title mb-1 text-white-50" style="font-size: 0.9rem;">TOTAL INVENTORY COST</h6>
            <h3 class="mb-0 text-white">${formatCurrency(
              totalInventoryCost
            )}</h3>
            <small class="text-white-50">Total value</small>
          </div>
        </div>
      </div>
    </div>

    ${tableHtml}
    ${generateBrandSummaryHtml(data)}
  `;

  $("#monthlyReportContent").html(html);
}

function generateTransferredReportPDF() {
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF("l", "mm", "a4");

  if (!currentReportData || currentReportType !== "transferred") {
    showErrorModal(
      "Please generate a transferred summary report first before exporting to PDF"
    );
    return;
  }

  const categoryFilter = $("#reportCategoryFilter").val() || "All Categories";
  const branchName = currentReportBranch;

  let dateSubtitle = "";
  let fileNameDate = new Date().toISOString().slice(0, 10);

  if (currentReportDate) {
    dateSubtitle = `For ${formatDate(currentReportDate)}`;
    fileNameDate = currentReportDate;
  } else if (currentReportMonth && currentReportMonth.includes("-")) {
    const [year, monthNum] = currentReportMonth.split("-");
    const monthName = new Date(year, monthNum - 1, 1).toLocaleString(
      "default",
      { month: "long" }
    );
    dateSubtitle = `For the Month of ${monthName} ${year}`;
    fileNameDate = currentReportMonth;
  } else if (currentReportStartDate && currentReportEndDate) {
    if (currentReportStartDate === currentReportEndDate) {
      dateSubtitle = `For ${formatDate(currentReportStartDate)}`;
      fileNameDate = currentReportStartDate;
    } else {
      dateSubtitle = `From ${formatDate(
        currentReportStartDate
      )} to ${formatDate(currentReportEndDate)}`;
      fileNameDate = `${currentReportStartDate}_to_${currentReportEndDate}`;
    }
  }

  const totalTransferred = currentReportSummary?.total_transferred || 0;
  const totalInventoryCost = currentReportSummary?.total_inventory_cost || 0;

  const pageWidth = doc.internal.pageSize.getWidth();
  const pageHeight = doc.internal.pageSize.getHeight();
  const leftRightMargin = 15;
  const topMargin = 15;
  let currentY = topMargin;

  
  doc.setFont("helvetica", "bold").setFontSize(16).setTextColor(0, 15, 113);
  doc.text("SOLID MOTORCYCLE DISTRIBUTORS, INC.", pageWidth / 2, currentY, {
    align: "center",
  });
  currentY += 10;
  doc.setFontSize(13).setTextColor(73, 80, 87);
  doc.text("SUMMARY OF TRANSFERRED STOCKS", pageWidth / 2, currentY, {
    align: "center",
  });
  currentY += 8;
  doc.setFontSize(10);
  doc.text(dateSubtitle, pageWidth / 2, currentY, { align: "center" });
  currentY += 6;
  currentY = addFiltersToPdf(doc, currentY);

  
  const tableColumns = [
    { header: "#", dataKey: "index" },
    { header: "Invoice Number", dataKey: "invoice_number" },
    { header: "Model", dataKey: "model" },
    { header: "Brand", dataKey: "brand" },
    { header: "Color", dataKey: "color" },
    { header: "Engine Number", dataKey: "engine_number" },
    { header: "Frame Number", dataKey: "frame_number" },
    { header: "Transfer Date", dataKey: "transfer_date" },
    { header: "Transferred To", dataKey: "transferred_to" },
    { header: "Inventory Cost", dataKey: "inventory_cost" },
  ];

  const tableRows =
    !currentReportData.data || currentReportData.data.length === 0
      ? []
      : currentReportData.data.map((item, index) => ({
          index: index + 1,
          invoice_number: item.invoice_number || "N/A",
          model: item.model,
          brand: item.brand,
          color: item.color,
          engine_number: item.engine_number,
          frame_number: item.frame_number,
          transfer_date: formatDate(item.transfer_date),
          transferred_to: item.transferred_to,
          inventory_cost: formatCurrency(item.inventory_cost),
        }));

  doc.autoTable({
    startY: currentY,
    head: [tableColumns.map((c) => c.header)],
    body: tableRows.map((row) => tableColumns.map((col) => row[col.dataKey])),
    theme: "striped",
    headStyles: {
      fillColor: [248, 249, 250],
      textColor: [73, 80, 87],
      fontStyle: "bold",
      fontSize: 8,
    },
    styles: { fontSize: 8, cellPadding: 2, textColor: 0 },
    columnStyles: {
      index: { halign: "center", cellWidth: 7 },
      brand: { cellWidth: 18 },
      transfer_date: { cellWidth: 19 },
      transferred_to: { cellWidth: 24, halign: "center" },
      inventory_cost: { halign: "right", cellWidth: 23 },
    },
    margin: { left: leftRightMargin, right: leftRightMargin },
  });

  let finalY = doc.autoTable.previous.finalY + 10;

  
  const cardWidth = (pageWidth - 2 * leftRightMargin - 10) / 2;
  const cardHeight = 20;

  if (finalY + cardHeight > pageHeight - 25) {
    doc.addPage();
    finalY = topMargin;
  }

  
  
  doc.setFillColor(0, 15, 113);
  doc.roundedRect(leftRightMargin, finalY, cardWidth, cardHeight, 3, 3, "F");
  doc.setFontSize(8).setTextColor(200, 200, 255).setFont("helvetica", "bold"); 
  doc.text("TOTAL TRANSFERRED", leftRightMargin + 10, finalY + 7);
  doc.setFontSize(12).setTextColor(255, 255, 255); 
  doc.text(String(totalTransferred), leftRightMargin + 10, finalY + 15); 

  const secondCardX = leftRightMargin + cardWidth + 10;
  doc.setFillColor(40, 167, 69);
  doc.roundedRect(secondCardX, finalY, cardWidth, cardHeight, 3, 3, "F");
  doc.setFontSize(8).setTextColor(200, 255, 200).setFont("helvetica", "bold"); 
  doc.text("TOTAL INVENTORY COST", secondCardX + 10, finalY + 7);
  doc.setFontSize(12).setTextColor(255, 255, 255); 
  doc.text(formatCurrency(totalInventoryCost), secondCardX + 10, finalY + 15);

  finalY += cardHeight;

  
  finalY = addBrandSummaryToPdf(doc, currentReportData.data, finalY);

  
  const generatedOn = new Date().toLocaleDateString("en-US", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  });
  const totalPages = doc.internal.getNumberOfPages();
  for (let i = 1; i <= totalPages; i++) {
    doc.setPage(i);
    doc.setFontSize(8);
    doc.setTextColor(108, 117, 125);
    doc.text(`Generated on: ${generatedOn}`, 10, pageHeight - 10);
    doc.text(`Page ${i} of ${totalPages}`, pageWidth / 2, pageHeight - 10, {
      align: "center",
    });
  }

  const safeBranchName = branchName.replace(/\s+/g, "_");
  doc.save(`Transferred_Summary_${fileNameDate}_${safeBranchName}.pdf`);
}


function generateReceivedSummary(
  month,
  branch,
  category = "all",
  brand = "all"
) {
  $("#monthlyReportContent").html(
    '<div class="text-center py-5"><div class="spinner-border text-black" role="status"></div></div>'
  );

  $.ajax({
    url: "../api/inventory_management.php",
    method: "GET",
    data: {
      action: "get_monthly_received_summary", 
      month: month,
      branch: branch,
      category: category,
      brand: brand,
    },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        currentReportData = response.data;
        currentReportMonth = response.month;
        currentReportBranch = response.branch;
        currentReportType = "received"; 
        currentReportSummary = response.summary;

        renderReceivedSummaryReport(
          response.data,
          response.month,
          response.branch,
          response.summary
        );
        $("#monthlyInventoryReportModal").modal("show");
      } else {
        showErrorModal(
          response.message || "Error generating received stocks summary"
        );
      }
    },
    error: function (xhr, status, error) {
      showErrorModal("Error generating received stocks summary: " + error);
    },
  });
}

function renderReceivedSummaryReport(response) {
  const { data, summary, branch, month, date, start_date, end_date } = response;

  let dateSubtitle = "";
  if (date) {
    dateSubtitle = `For ${formatDate(date)}`;
  } else if (month && month.includes("-")) {
    const [year, monthNum] = month.split("-");
    const monthName = new Date(year, monthNum - 1, 1).toLocaleString(
      "default",
      { month: "long" }
    );
    dateSubtitle = `For the Month of ${monthName} ${year}`;
  } else if (start_date && end_date) {
    if (start_date === end_date) {
      dateSubtitle = `For ${formatDate(start_date)}`;
    } else {
      dateSubtitle = `From ${formatDate(start_date)} to ${formatDate(
        end_date
      )}`;
    }
  }

  const totalReceived = summary?.total_received || 0;
  const totalInventoryCost = summary?.total_inventory_cost || 0;

  let tableHtml = "";
  if (!data || data.length === 0) {
    tableHtml = `
      <div class="alert alert-info text-center mt-4">
        <i class="bi bi-info-circle me-2"></i>
        No stocks received for the selected period at ${branch} branch.
      </div>
    `;
  } else {
    tableHtml = `
      <div class="table-responsive">
        <table class="table table-striped table-hover">
          <thead class="table-dark" style="position: sticky; top: 0; z-index: 10;">
            <tr>
              <th>#</th>
              <th>Invoice Number</th>
              <th>Model</th>
              <th>Brand</th>
              <th>Color</th>
              <th>Engine Number</th>
              <th>Frame Number</th>
              <th>Received Date</th>
              <th>Received From</th>
              <th class="text-end">Inventory Cost</th>
            </tr>
          </thead>
          <tbody>
    `;
    data.forEach((item, index) => {
      tableHtml += `
        <tr>
          <td>${index + 1}</td>
          <td>${escapeHtml(item.invoice_number || "N/A")}</td>
          <td>${escapeHtml(item.model)}</td>
          <td class="fw-bold">${escapeHtml(item.brand)}</td>
          <td>${escapeHtml(item.color)}</td>
          <td><code>${escapeHtml(item.engine_number)}</code></td>
          <td><code>${escapeHtml(item.frame_number)}</code></td>
          <td>${formatDate(item.date_received)}</td>
          <td><span class="badge bg-info">${escapeHtml(
            item.received_from
          )}</span></td>
          <td class="text-end fw-bold">${formatCurrency(
            item.inventory_cost
          )}</td>
        </tr>
      `;
    });
    tableHtml += `
          </tbody>
          <tfoot class="table-group-divider">
            <tr class="table-active">
              <td colspan="9" class="text-end fw-bold">Total:</td>
              <td class="text-end fw-bold">${formatCurrency(
                totalInventoryCost
              )}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    `;
  }

  let html = `
    <div class="report-header text-center mb-4">
        <div class="d-flex align-items-center justify-content-center mb-2">
            <div style="width: 40px; height: 2px; background: #000f71; margin-right: 15px;"></div>
            <h4 class="mb-0" style="color: #000f71; font-weight: 600; letter-spacing: 0.5px;">SOLID MOTORCYCLE DISTRIBUTORS, INC.</h4>
            <div style="width: 40px; height: 2px; background: #000f71; margin-left: 15px;"></div>
        </div>
        <h5 class="mb-2" style="color: #495057; font-weight: 500;">SUMMARY OF RECEIVED STOCKS</h5>
        <h6 class="mb-2 text-muted" style="font-weight: 400;">${dateSubtitle}</h6>
      ${buildFilterDisplayHtml()}
    </div>

    <div class="row mb-4">
        <div class="col-md-6 mb-3 mb-md-0">
            <div class="card border-0 shadow-sm text-center h-100" style="background: linear-gradient(135deg, #000f71, #1a237e); color: white;">
                <div class="card-body py-3">
                    <h6 class="card-title mb-1 text-white-50" style="font-size: 0.9rem;">TOTAL RECEIVED</h6>
                    <h3 class="mb-0 text-white">${totalReceived}</h3>
                    <small class="text-white-50">Motorcycles</small>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm text-center h-100" style="background: linear-gradient(135deg, #28a745, #20c997); color: white;">
                <div class="card-body py-3">
                    <h6 class="card-title mb-1 text-white-50" style="font-size: 0.9rem;">TOTAL INVENTORY COST</h6>
                    <h3 class="mb-0 text-white">${formatCurrency(
                      totalInventoryCost
                    )}</h3>
                    <small class="text-white-50">Total value</small>
                </div>
            </div>
        </div>
    </div>

    ${tableHtml}
    ${generateBrandSummaryHtml(data)}
  `;

  $("#monthlyReportContent").html(html);
}
function generateReceivedReportPDF() {
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF("l", "mm", "a4");

  if (!currentReportData || currentReportType !== "received") {
    showErrorModal(
      "Please generate a received stocks summary report first before exporting to PDF"
    );
    return;
  }

  let dateSubtitle = "";
  let fileNameDate = new Date().toISOString().slice(0, 10);

  if (currentReportDate) {
    dateSubtitle = `For ${formatDate(currentReportDate)}`;
    fileNameDate = currentReportDate;
  } else if (currentReportMonth && currentReportMonth.includes("-")) {
    const [year, monthNum] = currentReportMonth.split("-");
    const monthName = new Date(year, monthNum - 1, 1).toLocaleString(
      "default",
      { month: "long" }
    );
    dateSubtitle = `For the Month of ${monthName} ${year}`;
    fileNameDate = currentReportMonth;
  } else if (currentReportStartDate && currentReportEndDate) {
    if (currentReportStartDate === currentReportEndDate) {
      dateSubtitle = `For ${formatDate(currentReportStartDate)}`;
      fileNameDate = currentReportStartDate;
    } else {
      dateSubtitle = `From ${formatDate(
        currentReportStartDate
      )} to ${formatDate(currentReportEndDate)}`;
      fileNameDate = `${currentReportStartDate}_to_${currentReportEndDate}`;
    }
  }

  const pageWidth = doc.internal.pageSize.getWidth();
  const pageHeight = doc.internal.pageSize.getHeight();
  const marginLR = 15;
  const topMargin = 15;
  let currentY = topMargin;

  
  doc.setFont("helvetica", "bold").setFontSize(16).setTextColor(0, 15, 113);
  doc.text("SOLID MOTORCYCLE DISTRIBUTORS, INC.", pageWidth / 2, currentY, {
    align: "center",
  });
  currentY += 10;
  doc.setFontSize(13).setTextColor(73, 80, 87);
  doc.text("SUMMARY OF RECEIVED STOCKS", pageWidth / 2, currentY, {
    align: "center",
  });
  currentY += 8;
  doc.setFontSize(10);
  doc.text(dateSubtitle, pageWidth / 2, currentY, { align: "center" });
  currentY += 6;

  currentY = addFiltersToPdf(doc, currentY);
  
  const tableColumns = [
    { header: "#", dataKey: "index" },
    { header: "Invoice Number", dataKey: "invoice_number" },
    { header: "Model", dataKey: "model" },
    { header: "Brand", dataKey: "brand" },
    { header: "Color", dataKey: "color" },
    { header: "Engine Number", dataKey: "engine_number" },
    { header: "Frame Number", dataKey: "frame_number" },
    { header: "Received Date", dataKey: "date_received" },
    { header: "Received From", dataKey: "received_from" },
    { header: "Inventory Cost", dataKey: "inventory_cost" },
  ];
  const tableRows =
    !currentReportData.data || currentReportData.data.length === 0
      ? []
      : currentReportData.data.map((item, index) => ({
          index: index + 1,
          invoice_number: item.invoice_number || "N/A",
          model: item.model,
          brand: item.brand,
          color: item.color,
          engine_number: item.engine_number,
          frame_number: item.frame_number,
          date_received: formatDate(item.date_received),
          received_from: item.received_from,
          inventory_cost: formatCurrency(item.inventory_cost),
        }));
  const tableColumnStyles = {
    index: { halign: "center", cellWidth: 8 },
    invoice_number: { cellWidth: 28 },
    model: { cellWidth: 33 },
    brand: { cellWidth: 18 },
    color: { cellWidth: 18 },
    engine_number: { cellWidth: 33 },
    frame_number: { cellWidth: 33 },
    date_received: { cellWidth: 22 },
    received_from: { cellWidth: 25, halign: "center" },
    inventory_cost: { halign: "right", cellWidth: 25 },
  };

  doc.autoTable({
    startY: currentY,
    head: [tableColumns.map((c) => c.header)],
    body: tableRows.map((row) => tableColumns.map((col) => row[col.dataKey])),
    theme: "striped",
    headStyles: {
      fillColor: [248, 249, 250],
      textColor: [73, 80, 87],
      fontStyle: "bold",
      fontSize: 8,
    },
    styles: { fontSize: 8, cellPadding: 2, textColor: 0 },
    columnStyles: tableColumnStyles,
    margin: { left: marginLR, right: marginLR },
  });

  let finalY = doc.autoTable.previous.finalY + 10;

  
  const totalReceived = currentReportSummary?.total_received || 0;
  const totalInventoryCost = currentReportSummary?.total_inventory_cost || 0;
  const cardWidth = (pageWidth - 2 * marginLR - 10) / 2;
  const cardHeight = 20;

  if (finalY + cardHeight > pageHeight - 25) {
    doc.addPage();
    finalY = topMargin;
  }

  
  doc.setFillColor(0, 15, 113);
  doc.roundedRect(marginLR, finalY, cardWidth, cardHeight, 3, 3, "F");
  doc.setFontSize(8).setTextColor(200, 200, 255).setFont("helvetica", "bold");
  doc.text("TOTAL RECEIVED", marginLR + 10, finalY + 7);
  doc.setFontSize(12).setTextColor(255, 255, 255);
  doc.text(String(totalReceived), marginLR + 10, finalY + 15);

  
  const secondCardX = marginLR + cardWidth + 10;
  doc.setFillColor(40, 167, 69);
  doc.roundedRect(secondCardX, finalY, cardWidth, cardHeight, 3, 3, "F");
  doc.setFontSize(8).setTextColor(200, 255, 200).setFont("helvetica", "bold");
  doc.text("TOTAL INVENTORY COST", secondCardX + 10, finalY + 7);
  doc.setFontSize(12).setTextColor(255, 255, 255);
  doc.text(formatCurrency(totalInventoryCost), secondCardX + 10, finalY + 15);

  finalY += cardHeight;

  
  finalY = addBrandSummaryToPdf(doc, currentReportData.data, finalY);

  
  const generatedOn = new Date().toLocaleDateString("en-US", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  });
  const totalPages = doc.internal.getNumberOfPages();
  for (let i = 1; i <= totalPages; i++) {
    doc.setPage(i);
    doc.setFontSize(8);
    doc.setTextColor(108, 117, 125);
    doc.text(`Generated on: ${generatedOn}`, 10, pageHeight - 10);
    doc.text(`Page ${i} of ${totalPages}`, pageWidth / 2, pageHeight - 10, {
      align: "center",
    });
  }

  const safeBranchName = (currentReportBranch || "all").replace(/\s+/g, "_");
  doc.save(`Received_Summary_${fileNameDate}_${safeBranchName}.pdf`);
}


function renderDeliveredSummaryReport(response) {
  const { data, summary, month, date, start_date, end_date } = response;

  let dateSubtitle = "";
  if (date) {
    dateSubtitle = `For ${formatDate(date)}`;
  } else if (month) {
    const [year, monthNum] = month.split("-");
    const monthName = new Date(year, monthNum - 1, 1).toLocaleString(
      "default",
      { month: "long" }
    );
    dateSubtitle = `For the Month of ${monthName} ${year}`;
  } else if (start_date && end_date) {
    if (start_date === end_date) {
      dateSubtitle = `For ${formatDate(start_date)}`;
    } else {
      dateSubtitle = `From ${formatDate(start_date)} to ${formatDate(
        end_date
      )}`;
    }
  }

  const totalDelivered = summary?.total_delivered || 0;
  const totalInventoryCost = summary?.total_inventory_cost || 0;

  let tableHtml = "";
  if (!data || data.length === 0) {
    tableHtml = `<div class="alert alert-info text-center mt-4">No new stocks delivered for the selected period.</div>`;
  } else {
    tableHtml = `
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark" style="position: sticky; top: 0; z-index: 10;">
                    <tr>
                        <th>#</th><th>Invoice Number</th><th>Model</th><th>Brand</th>
                        <th>Color</th><th>Engine Number</th><th>Frame Number</th>
                        <th>Date Delivered</th><th class="text-end">Inventory Cost</th>
                    </tr>
                </thead>
                <tbody>`;
    data.forEach((item, index) => {
      tableHtml += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${escapeHtml(item.invoice_number || "N/A")}</td>
                    <td>${escapeHtml(item.model)}</td>
                    <td class="fw-bold">${escapeHtml(item.brand)}</td>
                    <td>${escapeHtml(item.color)}</td>
                    <td><code>${escapeHtml(item.engine_number)}</code></td>
                    <td><code>${escapeHtml(item.frame_number)}</code></td>
                    <td>${formatDate(item.date_delivered)}</td>
                   
                    <td class="text-end fw-bold">${formatCurrency(
                      item.inventory_cost
                    )}</td>
                </tr>`;
    });
    tableHtml += `
                </tbody>
                <tfoot class="table-group-divider">
                    <tr class="table-active">
                        <td colspan="9" class="text-end fw-bold">Total:</td>
                        <td class="text-end fw-bold">${formatCurrency(
                          totalInventoryCost
                        )}</td>
                    </tr>
                </tfoot>
            </table>
        </div>`;
  }

  let html = `
        <div class="report-header text-center mb-4">
            <div class="d-flex align-items-center justify-content-center mb-2">
                <div style="width: 40px; height: 2px; background: #000f71; margin-right: 15px;"></div>
                <h4 class="mb-0" style="color: #000f71; font-weight: 600;">SOLID MOTORCYCLE DISTRIBUTORS, INC.</h4>
                <div style="width: 40px; height: 2px; background: #000f71; margin-left: 15px;"></div>
            </div>
            <h5 class="mb-2" style="color: #495057;">SUMMARY OF DELIVERED STOCKS</h5>
            <h6 class="mb-2 text-muted">${dateSubtitle}</h6>
            ${buildFilterDisplayHtml()}
        </div>
        <div class="row mb-4">
            <div class="col-md-6 mb-3 mb-md-0">
                <div class="card border-0 shadow-sm text-center h-100" style="background: linear-gradient(135deg, #0d6efd, #0b5ed7); color: white;">
                    <div class="card-body py-3">
                        <h6 class="card-title mb-1 text-white-50">TOTAL DELIVERED</h6>
                        <h3 class="mb-0 text-white">${totalDelivered}</h3>
                        <small class="text-white-50">Motorcycles</small>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm text-center h-100" style="background: linear-gradient(135deg, #198754, #157347); color: white;">
                    <div class="card-body py-3">
                        <h6 class="card-title mb-1 text-white-50">TOTAL INVENTORY COST</h6>
                        <h3 class="mb-0 text-white">${formatCurrency(
                          totalInventoryCost
                        )}</h3>
                        <small class="text-white-50">Total value</small>
                    </div>
                </div>
            </div>
        </div>
        ${tableHtml}
        ${generateBrandSummaryHtml(data)}
    `;
  $("#monthlyReportContent").html(html);
}

function generateDeliveredReportPDF() {
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF("l", "mm", "a4");

  if (!currentReportData || currentReportType !== "delivered_stocks") {
    showErrorModal(
      "Please generate a delivered stocks summary report first before exporting to PDF"
    );
    return;
  }

  let dateSubtitle = "";
  let fileNameDate = new Date().toISOString().slice(0, 10);

  if (currentReportDate) {
    dateSubtitle = `For ${formatDate(currentReportDate)}`;
    fileNameDate = currentReportDate;
  } else if (currentReportMonth) {
    const [year, monthNum] = currentReportMonth.split("-");
    const monthName = new Date(year, monthNum - 1, 1).toLocaleString(
      "default",
      { month: "long" }
    );
    dateSubtitle = `For the Month of ${monthName} ${year}`;
    fileNameDate = currentReportMonth;
  } else if (currentReportStartDate && currentReportEndDate) {
    if (currentReportStartDate === currentReportEndDate) {
      dateSubtitle = `For ${formatDate(currentReportStartDate)}`;
      fileNameDate = currentReportStartDate;
    } else {
      dateSubtitle = `From ${formatDate(
        currentReportStartDate
      )} to ${formatDate(currentReportEndDate)}`;
      fileNameDate = `${currentReportStartDate}_to_${currentReportEndDate}`;
    }
  }

  const pageWidth = doc.internal.pageSize.getWidth();
  const pageHeight = doc.internal.pageSize.getHeight();
  const marginLR = 15;
  const topMargin = 15;
  let currentY = topMargin;

  
  doc.setFont("helvetica", "bold").setFontSize(16).setTextColor(0, 15, 113);
  doc.text("SOLID MOTORCYCLE DISTRIBUTORS, INC.", pageWidth / 2, currentY, {
    align: "center",
  });
  currentY += 10;
  doc.setFontSize(13).setTextColor(73, 80, 87);
  doc.text("SUMMARY OF DELIVERED STOCKS", pageWidth / 2, currentY, {
    align: "center",
  });
  currentY += 8;
  doc
    .setFontSize(10)
    .text(dateSubtitle, pageWidth / 2, currentY, { align: "center" });
  currentY += 6;
  currentY = addFiltersToPdf(doc, currentY);

  
  const tableColumns = [
    { header: "#", dataKey: "index" },
    { header: "Invoice Number", dataKey: "invoice_number" },
    { header: "Model", dataKey: "model" },
    { header: "Brand", dataKey: "brand" },
    { header: "Color", dataKey: "color" },
    { header: "Engine Number", dataKey: "engine_number" },
    { header: "Frame Number", dataKey: "frame_number" },
    { header: "Date Delivered", dataKey: "date_delivered" },
    { header: "Inventory Cost", dataKey: "inventory_cost" },
  ];
  const tableRows =
    !currentReportData.data || currentReportData.data.length === 0
      ? []
      : currentReportData.data.map((item, index) => ({
          index: index + 1,
          invoice_number: item.invoice_number || "N/A",
          model: item.model,
          brand: item.brand,
          color: item.color,
          engine_number: item.engine_number,
          frame_number: item.frame_number,
          date_delivered: formatDate(item.date_delivered),
          current_branch: item.current_branch,
          inventory_cost: formatCurrency(item.inventory_cost),
        }));

  doc.autoTable({
    startY: currentY,
    head: [tableColumns.map((c) => c.header)],
    body: tableRows.map((row) => tableColumns.map((col) => row[col.dataKey])),
    theme: "striped",
    headStyles: {
      fillColor: [248, 249, 250],
      textColor: [73, 80, 87],
      fontStyle: "bold",
      fontSize: 8,
    },
    styles: { fontSize: 8, cellPadding: 2, textColor: 0 },
    columnStyles: {
      0: { halign: "center", cellWidth: 8 },
      9: { halign: "right", cellWidth: 25 },
    },
    margin: { left: marginLR, right: marginLR },
  });

  let finalY = doc.autoTable.previous.finalY + 10;

  
  const totalDelivered = currentReportSummary?.total_delivered || 0;
  const totalInventoryCost = currentReportSummary?.total_inventory_cost || 0;
  const cardWidth = (pageWidth - 2 * marginLR - 10) / 2;
  const cardHeight = 20;

  if (finalY + cardHeight > pageHeight - 25) {
    doc.addPage();
    finalY = topMargin;
  }

  doc.setFillColor(13, 110, 253);
  doc.roundedRect(marginLR, finalY, cardWidth, cardHeight, 3, 3, "F");
  doc.setFontSize(8).setTextColor(200, 225, 255).setFont("helvetica", "bold");
  doc.text("TOTAL DELIVERED", marginLR + 10, finalY + 7);
  doc.setFontSize(12).setTextColor(255, 255, 255);
  doc.text(String(totalDelivered), marginLR + 10, finalY + 15);

  const secondCardX = marginLR + cardWidth + 10;
  doc.setFillColor(25, 135, 84);
  doc.roundedRect(secondCardX, finalY, cardWidth, cardHeight, 3, 3, "F");
  doc.setFontSize(8).setTextColor(200, 255, 220).setFont("helvetica", "bold");
  doc.text("TOTAL INVENTORY COST", secondCardX + 10, finalY + 7);
  doc.setFontSize(12).setTextColor(255, 255, 255);
  doc.text(formatCurrency(totalInventoryCost), secondCardX + 10, finalY + 15);

  finalY += cardHeight;
  finalY = addBrandSummaryToPdf(doc, currentReportData.data, finalY);

  
  
  const generatedOn = new Date().toLocaleDateString("en-US", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  });
  const totalPages = doc.internal.getNumberOfPages();
  for (let i = 1; i <= totalPages; i++) {
    doc.setPage(i);
    doc.setFontSize(8);
    doc.setTextColor(108, 117, 125);
    doc.text(`Generated on: ${generatedOn}`, 10, pageHeight - 10);
    doc.text(`Page ${i} of ${totalPages}`, pageWidth / 2, pageHeight - 10, {
      align: "center",
    });
  }
  

  const safeBranchName = (currentReportBranch || "all").replace(/\s+/g, "_");
  doc.save(`Delivered_Summary_${fileNameDate}_${safeBranchName}.pdf`);
}

function generateScrappedReport() {
  const month = $("#reportMonth").val();
  const branch = $("#reportBranch").val() || "all";
  const category = $("#reportCategoryFilter").val() || "all";
  const brand = $("#reportBrandFilter").val() || "all";

  if (!month) {
    showErrorModal("Please select a month.");
    return;
  }

  $("#monthlyReportOptionsModal").modal("hide");
  $("#monthlyReportContent").html(
    '<div class="text-center py-5"><div class="spinner-border text-black" role="status"></div></div>'
  );

  $.ajax({
    url: "../api/inventory_management.php",
    method: "GET",
    data: {
      action: "get_monthly_scrapped_summary",
      month: month,
      branch: branch,
      category: category,
      brand: brand,
    },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        currentReportData = response.data;
        currentReportMonth = response.month;
        currentReportBranch = response.branch;
        currentReportType = "scrapped";
        currentReportSummary = response.summary;
        currentReportCategory = category; 
        currentReportBrand = brand; 

        renderScrappedReport(response);
        $("#monthlyInventoryReportModal").modal("show");
      } else {
        showErrorModal(
          response.message || "Error generating scrapped units report"
        );
      }
    },
    error: function (xhr, status, error) {
      showErrorModal("Error generating scrapped units report: " + error);
    },
  });
}

function renderScrappedReport(response) {
  const [year, monthNum] = response.month.split("-");
  const monthName = new Date(year, monthNum - 1, 1).toLocaleString("default", {
    month: "long",
  });

  const branchName =
    response.branch === "all" ? "All Branches" : response.branch;
  const brandName = response.brand === "all" ? "All Brands" : response.brand;
  const categoryName =
    response.category === "all" ? "All Categories" : response.category;

  const totalScrapped = response.summary.total_scrapped || 0;
  const totalInventoryCost = response.summary.total_inventory_cost || 0;
  const { data, summary, branch, month, date, start_date, end_date } = response;

  let dateSubtitle = "";
  
  if (date) {
    dateSubtitle = `For ${formatDate(date)}`;
  } else if (month) {
    const [year, monthNum] = month.split("-");
    const monthName = new Date(year, monthNum - 1, 1).toLocaleString(
      "default",
      { month: "long" }
    );
    dateSubtitle = `For the Month of ${monthName} ${year}`;
  } else if (start_date && end_date) {
    dateSubtitle = `From ${formatDate(start_date)} to ${formatDate(end_date)}`;
  }

  let html = `
        <div class="report-header text-center mb-4">
            <div class="d-flex align-items-center justify-content-center mb-2">
                <div style="width: 40px; height: 2px; background: #000f71; margin-right: 15px;"></div>
                <h4 class="mb-0" style="color: #000f71; font-weight: 600; letter-spacing: 0.5px;">
                    SOLID MOTORCYCLE DISTRIBUTORS, INC.
                </h4>
                <div style="width: 40px; height: 2px; background: #000f71; margin-left: 15px;"></div>
            </div>
            <h5 class="mb-2" style="color: #495057; font-weight: 500;">MONTHLY SUMMARY OF SCRAPPED UNITS</h5>
            <h6 class="mb-2 text-muted" style="font-weight: 400;">${monthName} ${year}</h6>
           ${buildFilterDisplayHtml()}
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
                <div class="card border-0 shadow-sm text-center" style="background: linear-gradient(135deg, #dc3545, #c82333); color: white;">
                    <div class="card-body py-4">
                        <h6 class="card-title mb-2 text-white">TOTAL SCRAPPED</h6>
                        <h3 class="mb-0 text-white">${totalScrapped}</h3>
                        <small>Motorcycles scrapped</small>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm text-center" style="background: linear-gradient(135deg, #fd7e14, #e36209); color: white;">
                    <div class="card-body py-4">
                        <h6 class="card-title mb-2 text-white">TOTAL INVENTORY LOSS</h6>
                        <h3 class="mb-0 text-white">${formatCurrency(
                          totalInventoryCost
                        )}</h3>
                        <small>Total value scrapped</small>
                    </div>
                </div>
            </div>
        </div>
    `;

  
  if (
    response.summary_by_brand_branch &&
    response.summary_by_brand_branch.length > 0
  ) {
    html += `
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Summary by Brand and Branch</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Brand</th>
                                    <th>Branch</th>
                                    <th class="text-center">Units</th>
                                    <th class="text-end">Total Cost</th>
                                </tr>
                            </thead>
                            <tbody>
        `;

    response.summary_by_brand_branch.forEach((item) => {
      html += `
                <tr>
                    <td>${escapeHtml(item.brand)}</td>
                    <td>${escapeHtml(item.current_branch)}</td>
                    <td class="text-center">${item.count}</td>
                    <td class="text-end">${formatCurrency(item.total_cost)}</td>
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

  
  if (response.summary_by_reason && response.summary_by_reason.length > 0) {
    html += `
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Summary by Scrap Reason</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Reason</th>
                                    <th class="text-center">Units</th>
                                    <th class="text-end">Total Cost</th>
                                </tr>
                            </thead>
                            <tbody>
        `;

    response.summary_by_reason.forEach((item) => {
      const reason = item.scrap_reason || "No reason specified";
      html += `
                <tr>
                    <td>${escapeHtml(reason)}</td>
                    <td class="text-center">${item.count}</td>
                    <td class="text-end">${formatCurrency(item.total_cost)}</td>
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

  
  if (response.data && response.data.length > 0) {
    html += `
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Detailed List of Scrapped Units</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Brand</th>
                                    <th>Model</th>
                                    <th>Color</th>
                                    <th>Engine #</th>
                                    <th>Frame #</th>
                                    <th>Branch</th>
                                    <th>Scrap Date</th>
                                    <th>Reason</th>
                                    <th class="text-end">Cost</th>
                                </tr>
                            </thead>
                            <tbody>
        `;

    response.data.forEach((item) => {
      html += `
                <tr>
                    <td>${escapeHtml(item.brand)}</td>
                    <td>${escapeHtml(item.model)}</td>
                    <td>${escapeHtml(item.color)}</td>
                    <td><code>${escapeHtml(item.engine_number)}</code></td>
                    <td><code>${escapeHtml(item.frame_number)}</code></td>
                    <td>${escapeHtml(item.current_branch)}</td>
                    <td>${formatDate(item.scrap_date)}</td>
                    <td>${escapeHtml(item.scrap_reason || "N/A")}</td>
                    <td class="text-end">${formatCurrency(
                      item.inventory_cost
                    )}</td>
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
  } else {
    html += `
            <div class="alert alert-info text-center">
                <i class="bi bi-info-circle me-2"></i>
                No scrapped units found for ${monthName} ${year}.
            </div>
        `;
  }
  html += generateBrandSummaryHtml(response.data);
  $("#monthlyReportContent").html(html);
}

function generateScrappedReportPDF() {
  const { jsPDF } = window.jspdf;

  if (!currentReportData || currentReportType !== "scrapped") {
    showErrorModal(
      "Please generate a scrapped units report first before exporting to PDF."
    );
    return;
  }
  const doc = new jsPDF({ orientation: "landscape", unit: "mm", format: "a4" }); 

  let dateSubtitle = "";
  let fileNameDate = new Date().toISOString().slice(0, 10); 

  if (currentReportDate) {
    
    dateSubtitle = `For ${formatDate(currentReportDate)}`;
    fileNameDate = currentReportDate;
  } else if (currentReportMonth && currentReportMonth.includes("-")) {
    
    const [year, monthNum] = currentReportMonth.split("-");
    const monthName = new Date(year, monthNum - 1, 1).toLocaleString(
      "default",
      { month: "long" }
    );
    dateSubtitle = `For the Month of ${monthName} ${year}`;
    fileNameDate = currentReportMonth;
  } else if (currentReportStartDate && currentReportEndDate) {
    
    if (currentReportStartDate === currentReportEndDate) {
      dateSubtitle = `For ${formatDate(currentReportStartDate)}`;
      fileNameDate = currentReportStartDate;
    } else {
      dateSubtitle = `From ${formatDate(
        currentReportStartDate
      )} to ${formatDate(currentReportEndDate)}`;
      fileNameDate = `${currentReportStartDate}_to_${currentReportEndDate}`;
    }
  }

  const pageWidth = doc.internal.pageSize.getWidth();
  const pageHeight = doc.internal.pageSize.getHeight();
  const marginLR = 10;
  const marginBottom = 15;
  let currentY = 15; 

  doc.setFont("helvetica", "bold");
  doc.setFontSize(13);
  doc.setTextColor(0, 15, 113);
  doc.text("SOLID MOTORCYCLE DISTRIBUTORS, INC.", pageWidth / 2, currentY, {
    align: "center",
  });
  currentY += 9;

  doc.setFontSize(11);
  doc.setTextColor(73, 80, 87);
  doc.text("Summary of Scrapped Units", pageWidth / 2, currentY, {
    align: "center",
  });
  currentY += 4;

  doc.setFontSize(10);
  doc.setTextColor(0, 64, 133);
  doc.text(dateSubtitle, pageWidth / 2, currentY, { align: "center" });
  currentY += 6; 

  currentY = addFiltersToPdf(doc, currentY);
  doc.setDrawColor(0, 15, 113);
  doc.setLineWidth(0.8);
  doc.line(marginLR, currentY, pageWidth - marginLR, currentY);
  currentY += 4; 

  const groupedData = {};
  currentReportData.forEach((item) => {
    const branchName = item.current_branch || "Unknown Branch";
    if (!groupedData[branchName]) groupedData[branchName] = [];
    groupedData[branchName].push(item);
  });

  const columns = [
    { header: "Brand", dataKey: "brand" },
    { header: "Model", dataKey: "model" },
    { header: "Color", dataKey: "color" },
    { header: "Engine #", dataKey: "engine_number" },
    { header: "Frame #", dataKey: "frame_number" },
    { header: "Scrap Date", dataKey: "scrap_date" },
    { header: "Reason", dataKey: "scrap_reason" },
    { header: "Cost", dataKey: "inventory_cost" },
  ];

  function formatRows(items) {
    return items.map((item) => ({
      brand: item.brand,
      model: item.model,
      color: item.color,
      engine_number: item.engine_number,
      frame_number: item.frame_number,
      scrap_date: formatDate(item.scrap_date),
      scrap_reason: item.scrap_reason || "N/A",
      inventory_cost: formatCurrency(item.inventory_cost),
    }));
  }

  for (const branchName in groupedData) {
    const items = groupedData[branchName];
    if (currentY + 20 > pageHeight - marginBottom) {
      doc.addPage();
      currentY = 20;
    }

    doc.setFontSize(10).setTextColor(0, 64, 133).setFont("helvetica", "bold");
    doc.text(
      `${branchName} - ${items.length} unit/s scrapped`,
      marginLR,
      currentY
    );
    currentY += 5;

    doc.autoTable({
      startY: currentY,
      head: [columns.map((c) => c.header)],
      body: formatRows(items).map((r) => columns.map((c) => r[c.dataKey])),
      styles: {
        fontSize: 7,
        cellPadding: 1.5,
        valign: "middle",
        overflow: "linebreak",
      },
      headStyles: {
        fillColor: [248, 249, 250],
        textColor: [73, 80, 87],
        fontStyle: "bold",
        halign: "center",
      },
      theme: "striped",
      margin: { left: marginLR, right: marginLR },
      didDrawPage: (data) => {
        currentY = data.cursor.y + 7;
      },
    });
    currentY = doc.autoTable.previous.finalY + 10;
  } 

  const totalScrapped = currentReportSummary.total_scrapped;
  const totalInventoryCost = currentReportSummary.total_inventory_cost;
  const cardWidth = (pageWidth - 2 * marginLR - 10) / 2;
  const cardHeight = 30;

  if (currentY + cardHeight + marginBottom > pageHeight) {
    doc.addPage();
    currentY = 20;
  }

  function drawCard(x, y, title, value, subValue) {
    doc.setDrawColor(233, 236, 239);
    doc.setFillColor(248, 249, 250);
    doc.rect(x, y, cardWidth, cardHeight, "F");
    doc
      .setFontSize(8)
      .setTextColor(73, 80, 87)
      .setFont("helvetica", "bold")
      .text(title, x + cardWidth / 2, y + 8, { align: "center" });
    doc
      .setFontSize(16)
      .setTextColor(0, 64, 133)
      .setFont("helvetica", "bold")
      .text(String(value), x + cardWidth / 2, y + 18, { align: "center" });
    doc
      .setFontSize(9)
      .setTextColor(108, 117, 125)
      .setFont("helvetica", "normal")
      .text(subValue, x + cardWidth / 2, y + 25, { align: "center" });
  }

  drawCard(
    marginLR,
    currentY,
    "TOTAL SCRAPPED UNITS",
    totalScrapped,
    "Units scrapped"
  );
  drawCard(
    marginLR + cardWidth + 10,
    currentY,
    "TOTAL INVENTORY LOSS",
    formatCurrency(totalInventoryCost),
    "Total value scrapped"
  );
  let finalY = doc.autoTable.previous.finalY;
  addBrandSummaryToPdf(doc, currentReportData.data, finalY);
  const generatedOn = new Date().toLocaleDateString("en-US", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  });
  const totalPages = doc.internal.getNumberOfPages();
  for (let i = 1; i <= totalPages; i++) {
    doc.setPage(i);
    doc.setFontSize(8);
    doc.setTextColor(108, 117, 125);
    doc.text(`Generated on: ${generatedOn}`, marginLR, pageHeight - 10);
    doc.text(`Page ${i} of ${totalPages}`, pageWidth / 2, pageHeight - 10, {
      align: "center",
    });
  } 

  const safeBranch = (currentReportBranch || "all").replace(/\s+/g, "_");
  doc.save(`Scrapped_Units_Report_${fileNameDate}_${safeBranch}.pdf`);
}




/**
 * Renders the HTML for the Redeemed Units report modal.
 * @param {object} response The API response containing report data.
 */
function renderRedeemedReport(response) {
  const { data, summary, month, date, start_date, end_date } = response;

  let dateSubtitle = "";
  if (date) {
    dateSubtitle = `For ${formatDate(date)}`;
  } else if (month) {
    const [year, monthNum] = month.split("-");
    const monthName = new Date(year, monthNum - 1, 1).toLocaleString(
      "default",
      { month: "long" }
    );
    dateSubtitle = `For the Month of ${monthName} ${year}`;
  } else if (start_date && end_date) {
    dateSubtitle = `From ${formatDate(start_date)} to ${formatDate(end_date)}`;
  }

  const totalRedeemed = summary?.total_redeemed || 0;
  const totalAmountPaid = summary?.total_amount_paid || 0;

  let tableHtml = "";
  if (!data || data.length === 0) {
    tableHtml = `<div class="alert alert-info text-center mt-4">No redeemed units found for the selected criteria.</div>`;
  } else {
    tableHtml = `
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark" style="position: sticky; top: 0; z-index: 10;">
                    <tr>
                        <th>#</th><th>Invoice #</th><th>Model</th><th>Brand</th><th>Color</th>
                        <th>Engine #</th><th>Frame #</th><th>Redeem Date</th>
                        <th>Customer</th><th class="text-end">Amount Paid</th>
                    </tr>
                </thead>
                <tbody>`;
    data.forEach((item, index) => {
      tableHtml += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${escapeHtml(item.invoice_number || "N/A")}</td>
                    <td>${escapeHtml(item.model)}</td>
                    <td class="fw-bold">${escapeHtml(item.brand)}</td>
                    <td>${escapeHtml(item.color)}</td>
                    <td><code>${escapeHtml(item.engine_number)}</code></td>
                    <td><code>${escapeHtml(item.frame_number)}</code></td>
                    <td>${formatDate(item.redeem_date)}</td>
                    <td>${escapeHtml(item.redeemed_by_customer)}</td>
                    <td class="text-end fw-bold">${formatCurrency(
                      item.amount_paid
                    )}</td>
                </tr>`;
    });
    tableHtml += `
                </tbody>
                <tfoot class="table-group-divider">
                    <tr class="table-active">
                        <td colspan="9" class="text-end fw-bold">Total:</td>
                        <td class="text-end fw-bold">${formatCurrency(
                          totalAmountPaid
                        )}</td>
                    </tr>
                </tfoot>
            </table>
        </div>`;
  }

  let html = `
        <div class="report-header text-center mb-4">
            <h4 class="mb-1" style="color: #000f71;">SOLID MOTORCYCLE DISTRIBUTORS, INC.</h4>
            <h5 class="mb-2" style="color: #495057;">Summary of Redeemed Units</h5>
            <h6 class="mb-2 text-muted">${dateSubtitle}</h6>
            ${buildFilterDisplayHtml()}
        </div>
        <div class="row mb-4">
            <div class="col-md-6 mb-3 mb-md-0">
                <div class="card border-0 shadow-sm text-center h-100" style="background: linear-gradient(135deg, #198754, #157347); color: white;">
                    <div class="card-body py-3">
                        <h6 class="card-title mb-1 text-white-50">TOTAL REDEEMED UNITS</h6>
                        <h3 class="mb-0 text-white">${totalRedeemed}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm text-center h-100" style="background: linear-gradient(135deg, #0d6efd, #0b5ed7); color: white;">
                    <div class="card-body py-3">
                        <h6 class="card-title mb-1 text-white-50">TOTAL AMOUNT PAID</h6>
                        <h3 class="mb-0 text-white">${formatCurrency(
                          totalAmountPaid
                        )}</h3>
                    </div>
                </div>
            </div>
        </div>
        ${tableHtml}
        ${generateBrandSummaryHtml(data)}
    `;

  $("#monthlyReportContent").html(html);
}

/**
 * Generates and saves a PDF for the Redeemed Units report.
 */
/**
 * Generates and saves a PDF for the Redeemed Units report, styled like the other reports.
 */
function generateRedeemedReportPDF() {
  const { jsPDF } = window.jspdf;
  if (!currentReportData || currentReportType !== "redeemed") {
    showErrorModal(
      "Please generate a redeemed units report first before exporting to PDF."
    );
    return;
  }
  const doc = new jsPDF({ orientation: "landscape", unit: "mm", format: "a4" });

  
  let dateSubtitle = "";
  let fileNameDate = new Date().toISOString().slice(0, 10);

  if (currentReportDate) {
    dateSubtitle = `For ${formatDate(currentReportDate)}`;
    fileNameDate = currentReportDate;
  } else if (currentReportMonth) {
    const [year, monthNum] = currentReportMonth.split("-");
    const monthName = new Date(year, monthNum - 1, 1).toLocaleString(
      "default",
      { month: "long" }
    );
    dateSubtitle = `For the Month of ${monthName} ${year}`;
    fileNameDate = currentReportMonth;
  } else if (currentReportStartDate && currentReportEndDate) {
    dateSubtitle = `From ${formatDate(currentReportStartDate)} to ${formatDate(
      currentReportEndDate
    )}`;
    fileNameDate = `${currentReportStartDate}_to_${currentReportEndDate}`;
  }

  const pageWidth = doc.internal.pageSize.getWidth();
  const pageHeight = doc.internal.pageSize.getHeight();
  const marginLR = 10;
  const marginBottom = 20;
  let currentY = 15;

  doc.setFont("helvetica", "bold").setFontSize(14).setTextColor(0, 15, 113);
  doc.text("SOLID MOTORCYCLE DISTRIBUTORS, INC.", pageWidth / 2, currentY, {
    align: "center",
  });
  currentY += 10;
  doc.setFontSize(12).setTextColor(73, 80, 87);
  doc.text("Summary of Redeemed Units", pageWidth / 2, currentY, {
    align: "center",
  });
  currentY += 6;
  doc.setFontSize(10).setTextColor(0, 64, 133);
  doc.text(dateSubtitle, pageWidth / 2, currentY, { align: "center" });
  currentY += 6;
  currentY = addFiltersToPdf(doc, currentY);

  
  const groupedData = {};
  currentReportData.data.forEach((item) => {
    const branchName = item.current_branch || "Unknown Branch";
    if (!groupedData[branchName]) groupedData[branchName] = [];
    groupedData[branchName].push(item);
  });

  const columns = [
    { header: "#", dataKey: "index" },
    { header: "Model", dataKey: "model" },
    { header: "Color", dataKey: "color" },
    { header: "Brand", dataKey: "brand" },
    { header: "Engine #", dataKey: "engine_number" },
    { header: "Frame #", dataKey: "frame_number" },
    { header: "Redeem Date", dataKey: "redeem_date" },
    { header: "Customer", dataKey: "redeemed_by_customer" },
    { header: "Amount Paid", dataKey: "amount_paid" },
  ];

  function addBranchSection(branchName, items) {
    if (currentY + 15 > pageHeight - marginBottom) {
      doc.addPage();
      currentY = 20;
    }
    doc.setFontSize(11).setTextColor(0, 64, 133).setFont("helvetica", "bold");
    doc.text(
      `${branchName} - ${items.length} unit/s redeemed`,
      marginLR,
      currentY
    );
    currentY += 6;

    const rows = items.map((item, index) => ({
      index: index + 1,
      model: item.model,
      color: item.color,
      brand: item.brand,
      engine_number: item.engine_number,
      frame_number: item.frame_number,
      redeem_date: formatDate(item.redeem_date),
      redeemed_by_customer: item.redeemed_by_customer,
      amount_paid: formatCurrency(item.amount_paid),
    }));

    doc.autoTable({
      startY: currentY,
      head: [columns.map((c) => c.header)],
      body: rows.map((r) => columns.map((c) => r[c.dataKey])),
      styles: { fontSize: 8, cellPadding: 2, valign: "middle" },
      headStyles: {
        fillColor: [248, 249, 250],
        textColor: [73, 80, 87],
        fontStyle: "bold",
        halign: "center",
      },
      columnStyles: { 0: { halign: "center" }, 9: { halign: "right" } },
      margin: { left: marginLR, right: marginLR },
      theme: "striped",
      didDrawPage: (data) => {
        currentY = data.cursor.y;
      },
    });
    currentY = doc.autoTable.previous.finalY + 10;
  }

  Object.keys(groupedData)
    .sort()
    .forEach((branchName) => {
      addBranchSection(branchName, groupedData[branchName]);
    });

  
  const totalRedeemed = currentReportSummary?.total_redeemed || 0;
  const totalAmountPaid = currentReportSummary?.total_amount_paid || 0;
  const cardWidth = (pageWidth - 2 * marginLR - 10) / 2;
  const cardHeight = 20;

  if (currentY + cardHeight > pageHeight - marginBottom) {
    doc.addPage();
    currentY = 20;
  }

  function drawCard(x, y, width, height, title, mainValue, bgColor, textColor) {
    doc.setFillColor(...bgColor);
    doc.roundedRect(x, y, width, height, 3, 3, "F");
    doc
      .setFontSize(8)
      .setTextColor(...textColor)
      .setFont("helvetica", "bold");
    doc.text(title, x + width / 2, y + 7, { align: "center" });
    doc.setFontSize(12).setTextColor(255, 255, 255);
    doc.text(String(mainValue), x + width / 2, y + 15, { align: "center" });
  }

  drawCard(
    marginLR,
    currentY,
    cardWidth,
    cardHeight,
    "TOTAL REDEEMED UNITS",
    totalRedeemed,
    [25, 135, 84],
    [200, 255, 220]
  );
  drawCard(
    marginLR + cardWidth + 10,
    currentY,
    cardWidth,
    cardHeight,
    "TOTAL AMOUNT PAID",
    formatCurrency(totalAmountPaid),
    [13, 110, 253],
    [200, 225, 255]
  );

  currentY += cardHeight;
  currentY = addBrandSummaryToPdf(doc, currentReportData.data, currentY);

  
  addFootersToPdf(doc, "Redeemed Units Report");
  const safeBranch = (currentReportBranch || "all").replace(/\s+/g, "_");
  doc.save(`Redeemed_Units_Report_${fileNameDate}_${safeBranch}.pdf`);
}

function generateMotorcycleReport(branch, brandFilter) {
  // Hide options modal
  $("#monthlyReportOptionsModal").modal("hide");

  // Show loading spinner
  $("#monthlyReportContent").html(`
    <div class="text-center py-5">
      <div class="spinner-border text-black" role="status"></div>
      <p class="mt-3">Generating report, please wait...</p>
    </div>
  `);

  // Collect selected filters from UI
  const periodType = $("#periodTypeSelect").val() || "monthly";
  const selectedMonth = $("#reportMonthInput").val() || moment().format("YYYY-MM");
  const category = $("#categorySelect").val() || "all";
  const model = $("#modelSelect").val() || "all";
  const saleType = $("#saleTypeSelect").val() || "all";

  // AJAX request
  $.ajax({
    url: "../api/inventory_management.php",
    method: "GET",
    data: {
      action: "get_available_motorcycles_report",
      period_type: periodType,
      branch: branch,
      category: category,
      brand: brandFilter,
      model: model,
      sale_type: saleType,
      month: selectedMonth,
    },
    dataType: "json",
    success: function (response) {
      if (response.success && response.data && response.data.length > 0) {
        currentReportData = response;
        currentReportType = "motorcycle";

        renderMotorcycleReport(
          response,
          branch,
          brandFilter,
          response.total_available_units,
          response.total_inventory_cost,
          response.month
        );

        $("#monthlyInventoryReportModal").modal("show");
      } else {
        $("#monthlyReportContent").html(
          `<p class='text-center text-danger py-4'>No available motorcycles found for the selected criteria.</p>`
        );
      }
    },
    error: function (xhr, status, error) {
      $("#monthlyReportContent").html(
        `<p class='text-center text-danger py-4'>Error generating report: ${error}</p>`
      );
    },
  });
}

function renderMotorcycleReport(response, brandFilter, totalUnits, totalValue, month) {
  const { data, date, start_date, end_date } = response;

  // Determine date subtitle
  let dateSubtitle = "";
  if (start_date && end_date) {
    if (start_date === end_date) {
      dateSubtitle = `For ${formatDate(start_date)}`;
    } else {
      dateSubtitle = `From ${formatDate(start_date)} to ${formatDate(end_date)}`;
    }
  } else if (date) {
    dateSubtitle = `As of ${formatDate(date)}`;
  } else if (month) {
    const [year, monthNum] = month.split("-");
    const monthName = new Date(year, monthNum - 1, 1).toLocaleString("default", { month: "long" });
    dateSubtitle = `For the Month of ${monthName} ${year}`;
  }

  // Set modal title
  $("#monthlyInventoryReportModalLabel").text("Available Motorcycle Units Report");

  // Compute totals
  const computedTotalUnits = data.length;
  const computedTotalValue = data.reduce(
    (sum, item) => sum + (parseFloat(item.inventory_cost) || 0),
    0
  );

  // Build table rows
  let tableRows = "";
  data.forEach((item, index) => {
    tableRows += `
      <tr>
        <td>${index + 1}</td>
        <td>${escapeHtml(item.model)}</td>
        <td>${escapeHtml(item.color)}</td>
        <td>${escapeHtml(item.brand)}</td>
        <td><code>${escapeHtml(item.engine_number)}</code></td>
        <td><code>${escapeHtml(item.frame_number)}</code></td>
        <td class="text-end">${formatCurrency(item.inventory_cost)}</td>
      </tr>`;
  });

  // Build full HTML
  const html = `
    <div class="report-header text-center mb-4">
      <div class="d-flex align-items-center justify-content-center mb-2">
        <div style="width: 40px; height: 2px; background: #000f71; margin-right: 15px;"></div>
        <h4 class="mb-0" style="color: #000f71; font-weight: 600;">
          SOLID MOTORCYCLE DISTRIBUTORS, INC.
        </h4>
        <div style="width: 40px; height: 2px; background: #000f71; margin-left: 15px;"></div>
      </div>
      <h5 class="mb-2" style="color: #495057;">AVAILABLE MOTORCYCLE UNITS REPORT</h5>
      <h6 class="mb-2 text-muted">${dateSubtitle}</h6>
      ${buildFilterDisplayHtml()}
    </div>

    <div class="row mb-4">
      <div class="col-md-6 mb-3 mb-md-0">
        <div class="card border-0 shadow-sm text-center h-100" 
             style="background: linear-gradient(135deg, #0d6efd, #0b5ed7); color: white;">
          <div class="card-body py-3">
            <h6 class="card-title mb-1 text-white-50" style="font-size: 0.9rem;">
              TOTAL AVAILABLE UNITS
            </h6>
            <h3 class="mb-0 text-white">${computedTotalUnits}</h3>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card border-0 shadow-sm text-center h-100" 
             style="background: linear-gradient(135deg, #198754, #157347); color: white;">
          <div class="card-body py-3">
            <h6 class="card-title mb-1 text-white-50" style="font-size: 0.9rem;">
              TOTAL INVENTORY VALUE
            </h6>
            <h3 class="mb-0 text-white">${formatCurrency(computedTotalValue)}</h3>
          </div>
        </div>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-header bg-dark text-white py-2">
        <h6 class="mb-0">Available Units (${computedTotalUnits} Total)</h6>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-striped table-hover mb-0">
            <thead class="table-dark">
              <tr>
                <th>#</th>
                <th>Model</th>
                <th>Color</th>
                <th>Brand</th>
                <th>Engine Number</th>
                <th>Frame Number</th>
                <th class="text-end">Inventory Cost</th>
              </tr>
            </thead>
            <tbody>${tableRows}</tbody>
          </table>
        </div>
      </div>
    </div>

    ${generateBrandSummaryHtml(data)}
  `;

  $("#monthlyReportContent").html(html);
}


function generateMotorcycleReportPDF() {
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF("l", "mm", "a4");

  // Ensure report data exists
  if (!currentReportData || currentReportType !== "motorcycle") {
    showErrorModal(
      "Please generate an available motorcycle units report first before exporting to PDF."
    );
    return;
  }

  let dateSubtitle = "";
  let fileNameDate = new Date().toISOString().slice(0, 10);

  // Determine which date/period to display
  if (currentReportDate) {
    dateSubtitle = `As of ${formatDate(currentReportDate)}`;
    fileNameDate = currentReportDate;
  } else if (currentReportMonth && currentReportMonth.includes("-")) {
    const [year, monthNum] = currentReportMonth.split("-");
    const monthName = new Date(year, monthNum - 1, 1).toLocaleString("default", {
      month: "long",
    });
    dateSubtitle = `For the Month of ${monthName} ${year}`;
    fileNameDate = currentReportMonth;
  } else if (currentReportStartDate && currentReportEndDate) {
    if (currentReportStartDate === currentReportEndDate) {
      dateSubtitle = `For ${formatDate(currentReportStartDate)}`;
      fileNameDate = currentReportStartDate;
    } else {
      dateSubtitle = `From ${formatDate(
        currentReportStartDate
      )} to ${formatDate(currentReportEndDate)}`;
      fileNameDate = `${currentReportStartDate}_to_${currentReportEndDate}`;
    }
  }

  const pageWidth = doc.internal.pageSize.getWidth();
  const pageHeight = doc.internal.pageSize.getHeight();
  const marginLR = 10;
  const marginBottom = 20;
  let currentY = 15;

  // ===== HEADER =====
  doc.setFont("helvetica", "bold").setFontSize(14).setTextColor(0, 15, 113);
  doc.text("SOLID MOTORCYCLE DISTRIBUTORS, INC.", pageWidth / 2, currentY, {
    align: "center",
  });
  currentY += 10;
  doc.setFontSize(12).setTextColor(73, 80, 87);
  doc.text("AVAILABLE MOTORCYCLE UNITS REPORT", pageWidth / 2, currentY, {
    align: "center",
  });
  currentY += 6;
  doc.setFontSize(10).setTextColor(0, 64, 133);
  doc.text(dateSubtitle, pageWidth / 2, currentY, { align: "center" });
  currentY += 6;

  // Add filters section (optional UI info)
  currentY = addFiltersToPdf(doc, currentY);

  // ===== TABLE =====
  const columns = [
    { header: "#", dataKey: "no" },
    { header: "MODEL", dataKey: "model" },
    { header: "COLOR", dataKey: "color" },
    { header: "BRAND", dataKey: "brand" },
    { header: "ENGINE NUMBER", dataKey: "engine_number" },
    { header: "FRAME NUMBER", dataKey: "frame_number" },
    { header: "INVENTORY COST", dataKey: "inventory_cost" },
  ];

  const rows = currentReportData.data.map((item, i) => ({
    no: i + 1,
    model: item.model,
    color: item.color,
    brand: item.brand,
    engine_number: item.engine_number,
    frame_number: item.frame_number,
    inventory_cost: formatCurrency(item.inventory_cost),
  }));

  doc.autoTable({
    startY: currentY,
    head: [columns.map((c) => c.header)],
    body: rows.map((r) => columns.map((c) => r[c.dataKey])),
    styles: { fontSize: 8, cellPadding: 2 },
    headStyles: {
      fillColor: [13, 110, 253],
      textColor: [255, 255, 255],
      fontStyle: "bold",
    },
    margin: { left: marginLR, right: marginLR },
    theme: "striped",
  });

  currentY = doc.autoTable.previous.finalY + 10;

  // ===== TOTALS =====
  const totalUnits = currentReportData.data.length;
  const totalValue = currentReportData.data.reduce(
    (sum, item) => sum + (parseFloat(item.inventory_cost) || 0),
    0
  );

  const cardWidth = (pageWidth - 2 * marginLR - 10) / 2;
  const cardHeight = 20;

  if (currentY + cardHeight > pageHeight - marginBottom) {
    doc.addPage();
    currentY = 20;
  }

  function drawCard(x, y, width, height, title, value, bgColor) {
    doc.setFillColor(...bgColor);
    doc.roundedRect(x, y, width, height, 3, 3, "F");
    doc
      .setFontSize(8)
      .setTextColor(255, 255, 255)
      .setFont("helvetica", "bold");
    doc.text(title, x + width / 2, y + 7, { align: "center" });
    doc.setFontSize(12);
    doc.text(String(value), x + width / 2, y + 15, { align: "center" });
  }

  drawCard(
    marginLR,
    currentY,
    cardWidth,
    cardHeight,
    "TOTAL AVAILABLE UNITS",
    totalUnits,
    [13, 110, 253]
  );

  drawCard(
    marginLR + cardWidth + 10,
    currentY,
    cardWidth,
    cardHeight,
    "TOTAL INVENTORY VALUE",
    formatCurrency(totalValue),
    [25, 135, 84]
  );

  currentY += cardHeight + 10;

  // ===== BRAND SUMMARY =====
  currentY = addBrandSummaryToPdf(doc, currentReportData.data, currentY);

  // ===== FOOTER =====
  const generatedOn = new Date().toLocaleDateString("en-US", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  });
  const totalPages = doc.internal.getNumberOfPages();
  for (let i = 1; i <= totalPages; i++) {
    doc.setPage(i);
    doc.setFontSize(8).setTextColor(108, 117, 125);
    doc.text(`Generated on: ${generatedOn}`, 10, pageHeight - 10);
    doc.text(`Page ${i} of ${totalPages}`, pageWidth / 2, pageHeight - 10, {
      align: "center",
    });
  }

  // ===== SAVE FILE =====
  const safeBranch = (currentReportBranch || "all").replace(/\s+/g, "_");
  const safeBrand = (currentReportBrand || "all").replace(/\s+/g, "_");
  const safeCategory = (currentReportCategory || "all").replace(/\s+/g, "_");
  doc.save(
    `Available_Units_Report_${fileNameDate}_${safeBranch}_${safeBrand}_${safeCategory}.pdf`
  );
}




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
        showFieldError(
          $element,
          "This engine number already exists in the system"
        );
      } else {
        clearFieldError($element);
      }
    },
    error: function () {
      clearFieldError($element);
    },
  });
}
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
        showFieldError(
          $element,
          "This frame number already exists in the system"
        );
      } else {
        clearFieldError($element);
      }
    },
    error: function () {
      clearFieldError($element);
    },
  });
}

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





function populateBranchesDropdown() {
  const branches = [
    "ALL",
    "HEADOFFICE",
    "KINGDOM",
    "TANQUE",
    "DFISHER",
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
    "PEMDI BACOLOD",
    "EEMSI-GUIMARAS",
    "INFINITY BACOLOD",
    "AJUY",
    "MINDORO ROXAS",
    "3S MINDORO",
    "MINDORO-MB",
    "MINDORO MANSALAY",
    "K-RIDERS ROXAS",
    "IBAJAY",
    "NUMANCIA",
    "CFCIPRC",
  ];

  const $dropdown = $("#reportBranch");
  $dropdown.empty().append('<option value="">Select Branch</option>');

  branches.forEach((branch) => {
    $dropdown.append(`<option value="${branch}">${branch}</option>`);
  });
}
function formatDate(dateString) {
  if (!dateString || dateString === "0000-00-00") return "N/A";
  const date = new Date(dateString);
  
  const userTimezoneOffset = date.getTimezoneOffset() * 60000;
  const adjustedDate = new Date(date.getTime() + userTimezoneOffset);
  return adjustedDate.toLocaleDateString("en-US", {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
  });
}

function escapeHtml(text) {
  if (text === null || text === undefined) return "";
  return String(text).replace(
    /[&<>"']/g,
    (m) =>
      ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;" }[
        m
      ])
  );
}

function getTransferStatusBadge(status) {
  
  const lowerStatus = String(status || "").toLowerCase();
  switch (lowerStatus) {
    case "in-transit":
      return '<span class="badge bg-warning text-dark">In-transit</span>';
    case "completed":
      return '<span class="badge bg-success">Completed</span>';
    case "rejected":
      return '<span class="badge bg-danger">Rejected</span>';
    default:
      return '<span class="badge bg-secondary">Unknown</span>';
  }
}

/**
 * Formats a full timestamp string (YYYY-MM-DD HH:MM:SS) into a readable format.
 * @param {string} timestampString - The timestamp from the database.
 * @returns {string} - Formatted date and time (e.g., "10/14/2025, 09:52 AM").
 */
function formatDateTime(timestampString) {
  if (!timestampString || timestampString === "0000-00-00 00:00:00")
    return "N/A";
  const date = new Date(timestampString);
  
  const userTimezoneOffset = date.getTimezoneOffset() * 60000;
  const adjustedDate = new Date(date.getTime() + userTimezoneOffset);
  return adjustedDate.toLocaleString("en-US", {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
    hour12: true,
  });
}
function formatCurrency(amount) {
  if (amount === null || amount === undefined) return "0.00";

  let amountStr = String(amount);

  let cleaned = amountStr.replace(/[±,]/g, "").replace(/[^0-9.-]/g, "");

  const parts = cleaned.split(".");
  if (parts.length > 2) {
    cleaned = parts[0] + "." + parts.slice(1).join("");
  }

  let num = Number(cleaned);

  if (isNaN(num)) {
    return "0.00";
  }

  num = Math.abs(num);

  return num.toLocaleString("en-PH", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
}

function capitalizeFirstLetter(string) {
  return string.charAt(0).toUpperCase() + string.slice(1);
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

function showInfoModal(message) {
  $("#infoMessage").text(message);
  $("#infoModal").modal("show");
  setTimeout(() => {
    $("#infoModal").modal("hide");
  }, 3000);
}

function groupByModel(items) {
  return items.reduce((groups, item) => {
    const key = `${item.brand} ${item.model}`;
    if (!groups[key]) groups[key] = [];
    groups[key].push(item);
    return groups;
  }, {});
}

function showPdfLoadingModal() {
  const modalId = "pdfLoadingModal";
  let modal = document.getElementById(modalId);

  if (!modal) {
    const modalHtml = `
            <div class="modal fade" id="${modalId}" tabindex="-1" data-bs-backdrop="static">
                <div class="modal-dialog modal-sm">
                    <div class="modal-content">
                        <div class="modal-body text-center">
                            <div class="spinner-border text-black mb-3" role="status"></div>
                            <p>Generating PDF... Please wait</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
    document.body.insertAdjacentHTML("beforeend", modalHtml);
    modal = document.getElementById(modalId);
  }

  const bsModal = new bootstrap.Modal(modal);
  bsModal.show();

  return { modal, bsModal };
}

function hidePdfLoadingModal(loadingModal) {
  if (loadingModal && loadingModal.bsModal) {
    loadingModal.bsModal.hide();
  }
}

function simplifyForPdf(container) {
  const buttons = container.querySelectorAll("button");
  buttons.forEach((button) => button.remove());

  const inputs = container.querySelectorAll("input, select, textarea");
  inputs.forEach((input) => input.remove());

  const tables = container.querySelectorAll("table");
  tables.forEach((table) => {
    table.style.display = "table";
    table.style.width = "100%";
    table.classList.add("table-sm");
  });

  const elements = container.querySelectorAll("*");
  elements.forEach((el) => {
    if (el.style.overflow === "hidden") {
      el.style.overflow = "visible";
    }
  });

  container.querySelectorAll(".d-none").forEach((el) => {
    el.classList.remove("d-none");
    el.style.display = "block";
  });
}

/**
 * Generates an HTML table summarizing the quantity of items per brand.
 * @param {Array<Object>} data The array of report data items. Each item must have a 'brand' property.
 * @returns {string} The HTML string for the summary table.
 */
function generateBrandSummaryHtml(data) {
  if (!data || data.length === 0) {
    return "";
  }

  const brandCounts = data.reduce((acc, item) => {
    const brand = item.brand || "Unknown";
    acc[brand] = (acc[brand] || 0) + 1;
    return acc;
  }, {});

  const sortedBrands = Object.keys(brandCounts).sort();
  if (sortedBrands.length === 0) {
    return "";
  }

  let summaryHtml = `
        <div class="mt-4 pt-4 border-top">
            <h6 class="text-center mb-3 text-black fw-bold">SUMMARY OF QUANTITY PER BRAND</h6>
            <div class="d-flex justify-content-center">
                <table class="table table-sm table-bordered" style="max-width: 400px;">
                    <thead class="table-light">
                        <tr>
                            <th>Brand</th>
                            <th class="text-center">Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
    `;

  let totalQuantity = 0;
  sortedBrands.forEach((brand) => {
    const count = brandCounts[brand];
    summaryHtml += `
            <tr>
                <td>${escapeHtml(brand)}</td>
                <td class="text-center">${count}</td>
            </tr>
        `;
    totalQuantity += count;
  });

  summaryHtml += `
                    </tbody>
                    <tfoot class="table-group-divider">
                        <tr class="table-primary fw-bold">
                            <td>TOTAL</td>
                            <td class="text-center">${totalQuantity}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    `;

  return summaryHtml;
}

/**
 * Adds a brand summary table to a jsPDF document.
 * @param {jsPDF} doc The jsPDF document instance.
 * @param {Array<Object>} data The array of report data items.
 * @param {number} startY The Y position to start drawing the table.
 * @returns {number} The new Y position after the table.
 */
function addBrandSummaryToPdf(doc, data, startY) {
  if (!data || data.length === 0) {
    return startY;
  }

  const brandCounts = data.reduce((acc, item) => {
    const brand = item.brand || "Unknown";
    acc[brand] = (acc[brand] || 0) + 1;
    return acc;
  }, {});

  const sortedBrands = Object.keys(brandCounts).sort();
  if (sortedBrands.length === 0) {
    return startY;
  }

  const body = [];
  let totalQuantity = 0;
  sortedBrands.forEach((brand) => {
    const count = brandCounts[brand];
    body.push([brand, { content: count, styles: { halign: "center" } }]);
    totalQuantity += count;
  });
  body.push([
    {
      content: "TOTAL",
      styles: { fontStyle: "bold", fillColor: [230, 242, 255] },
    },
    {
      content: totalQuantity,
      styles: {
        fontStyle: "bold",
        halign: "center",
        fillColor: [230, 242, 255],
      },
    },
  ]);

  let finalY = startY;

  
  const tableHeight = (body.length + 2) * 8; 
  if (startY + tableHeight > doc.internal.pageSize.getHeight() - 20) {
    doc.addPage();
    finalY = 20; 
  } else {
    finalY += 10; 
  }

  doc.setFontSize(10);
  doc.setFont("helvetica", "bold");
  doc.setTextColor(0, 0, 0);
  doc.text(
    "SUMMARY OF QUANTITY PER BRAND",
    doc.internal.pageSize.getWidth() / 2,
    finalY,
    { align: "center" }
  );
  finalY += 5;

  doc.autoTable({
    startY: finalY,
    head: [["Brand", "Quantity"]],
    body: body,
    theme: "grid",
    headStyles: {
      fillColor: [248, 249, 250],
      textColor: [73, 80, 87],
      fontStyle: "bold",
      halign: "center",
    },
    styles: { fontSize: 9 },
    columnStyles: {
      1: { halign: "center" },
    },
    margin: {
      left: doc.internal.pageSize.getWidth() / 3,
      right: doc.internal.pageSize.getWidth() / 3,
    },
    didDrawPage: (data) => {
      finalY = data.cursor.y;
    },
  });

  return doc.autoTable.previous.finalY;
}

/**
 * Generates an HTML string for displaying the current report filters.
 * @returns {string} The formatted HTML string for the filters.
 */
function buildFilterDisplayHtml() {
  const filters = [];

  
  if (currentReportBranch && currentReportBranch.toLowerCase() !== "all") {
    filters.push(`Branch: ${escapeHtml(currentReportBranch)}`);
  }

  
  if (
    currentReportCategory &&
    currentReportCategory.toLowerCase() !== "all" &&
    currentReportCategory !== ""
  ) {
    filters.push(`Category: ${escapeHtml(currentReportCategory)}`);
  }

  
  if (
    currentReportBrand &&
    currentReportBrand.toLowerCase() !== "all" &&
    currentReportBrand !== ""
  ) {
    filters.push(`Brand: ${escapeHtml(currentReportBrand)}`);
  }

  
  if (
    currentReportModel &&
    currentReportModel.toLowerCase() !== "all" &&
    currentReportModel !== ""
  ) {
    filters.push(`Model(s): ${escapeHtml(currentReportModel)}`);
  }

  
  if (
    currentReportSaleType &&
    currentReportSaleType.toLowerCase() !== "all" &&
    currentReportSaleType !== ""
  ) {
    filters.push(`Sale Type: ${escapeHtml(currentReportSaleType)}`);
  }

  if (filters.length === 0) {
    return '<p class="report-filters" style="color: red; text-transform: uppercase; font-weight: bold; font-size: 0.9rem;">FILTERS: ALL</p>';
  }

  return `<p class="report-filters" style="color: red; text-transform: uppercase; font-weight: bold; font-size: 0.9rem;">
                ${filters.join(" | ")}
            </p>`;
}

/**
 * Draws a formatted filter string onto a jsPDF document.
 * @param {jsPDF} doc The jsPDF document instance.
 * @param {number} currentY The current Y position to start drawing from.
 * @returns {number} The new Y position after drawing the text.
 */
function addFiltersToPdf(doc, currentY) {
  const filters = [];
  const pageWidth = doc.internal.pageSize.getWidth();

  
  if (currentReportBranch && currentReportBranch.toLowerCase() !== "all") {
    filters.push(`Branch: ${currentReportBranch}`);
  }
  if (
    currentReportCategory &&
    currentReportCategory.toLowerCase() !== "all" &&
    currentReportCategory !== ""
  ) {
    filters.push(`Category: ${currentReportCategory}`);
  }
  if (
    currentReportBrand &&
    currentReportBrand.toLowerCase() !== "all" &&
    currentReportBrand !== ""
  ) {
    filters.push(`Brand: ${currentReportBrand}`);
  }
  if (
    currentReportModel &&
    currentReportModel.toLowerCase() !== "all" &&
    currentReportModel !== ""
  ) {
    filters.push(`Model(s): ${currentReportModel}`);
  }
  if (
    currentReportSaleType &&
    currentReportSaleType.toLowerCase() !== "all" &&
    currentReportSaleType !== ""
  ) {
    filters.push(`Sale Type: ${currentReportSaleType}`);
  }

  let filterString = "FILTERS: ALL";
  if (filters.length > 0) {
    filterString = filters.join(" | ");
  }

  
  doc.setFontSize(9);
  doc.setTextColor(220, 53, 69); 
  doc.setFont("helvetica", "bold");
  doc.text(filterString.toUpperCase(), pageWidth / 2, currentY, {
    align: "center",
  });

  
  return currentY + 7;
}

/**
 * Converts a date string from "mm/dd/yyyy" to "yyyy-mm-dd" for API submission.
 * @param {string} dateString The date in "mm/dd/yyyy" format.
 * @returns {string} The date in "yyyy-mm-dd" format or an empty string.
 */
function formatDateForAPI(dateString) {
  if (!dateString || !dateString.includes("/")) return dateString; 
  const [month, day, year] = dateString.split("/");
  if (month && day && year) {
    return `${year}-${month.padStart(2, "0")}-${day.padStart(2, "0")}`;
  }
  return "";
}

/**
 * Adds a standardized footer with a timestamp and page numbers to each page of a jsPDF document.
 * @param {jsPDF} doc The jsPDF document instance.
 * @param {string} reportTitle A short title for the report to be included in the footer.
 */
function addFootersToPdf(doc, reportTitle) {
  const pageCount = doc.internal.getNumberOfPages();
  const pageWidth = doc.internal.pageSize.getWidth();
  const pageHeight = doc.internal.pageSize.getHeight();
  const genTime = new Date().toLocaleString("en-US", {
    dateStyle: "full",
    timeStyle: "short",
  });

  for (let i = 1; i <= pageCount; i++) {
    doc.setPage(i); 
    doc.setFontSize(8);
    doc.setTextColor(108, 117, 125); 

    
    doc.text(`Generated on: ${genTime}`, 10, pageHeight - 10);

    
    const centerText = `Page ${i} of ${pageCount} | ${reportTitle}`;
    doc.text(centerText, pageWidth / 2, pageHeight - 10, { align: "center" });
  }
}
function populateBranchesDropdowns(selectors, callback) {
  const branches = [
    "ALL",
    "HEADOFFICE",
    "KINGDOM",
    "TANQUE",
    "DFISHER",
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
    "PEMDI BACOLOD",
    "EEMSI-GUIMARAS",
    "INFINITY BACOLOD",
    "AJUY",
    "MINDORO ROXAS",
    "3S MINDORO",
    "MINDORO-MB",
    "MINDORO MANSALAY",
    "K-RIDERS ROXAS",
    "IBAJAY",
    "NUMANCIA",
    "CFCIPRC",
  ];

  selectors.forEach((sel) => {
    const select = $(sel);
    select.empty();
    branches.forEach((branch) => {
      select.append(`<option value="${branch}">${branch}</option>`);
    });
  });

  if (typeof callback === "function") callback();
}

/**
 * Renders the lists of current, added, and removed items in the manage transfer modal.
 */
function renderManagingTransferLists() {
  const initialList = $("#managingTransferInitialList");
  initialList.empty();
  let totalInitial = 0;
  let itemsAddedCount = managingTransfer.itemsToAdd.length;
  let itemsRemovedCount = managingTransfer.itemsToRemove.length;

  
  managingTransfer.initialItems.forEach((item) => {
    if (!managingTransfer.itemsToRemove.includes(item.id)) {
      totalInitial++;
      initialList.append(`
                <div class="transfer-item d-flex justify-content-between align-items-center">
                    <span>${escapeHtml(item.brand)} ${escapeHtml(
        item.model
      )} <small class="text-muted">(${escapeHtml(
        item.engine_number
      )})</small></span>
                    <button class="btn btn-sm btn-outline-danger" onclick="removeItemFromTransfer(${
                      item.id
                    })"><i class="bi bi-x-lg"></i></button>
                </div>
            `);
    }
  });

  
  managingTransfer.itemsToRemove.forEach((itemId) => {
    const item = managingTransfer.initialItems.find((i) => i.id === itemId);
    if (item) {
      initialList.append(`
                <div class="transfer-item to-be-removed d-flex justify-content-between align-items-center">
                    <span>${escapeHtml(item.brand)} ${escapeHtml(
        item.model
      )} <small>(${escapeHtml(item.engine_number)})</small></span>
                    <button class="btn btn-sm btn-outline-secondary" onclick="undoRemoveFromTransfer(${
                      item.id
                    })"><i class="bi bi-arrow-counterclockwise"></i></button>
                </div>
            `);
    }
  });

  
  managingTransfer.itemsToAdd.forEach((item) => {
    initialList.append(`
            <div class="transfer-item to-be-added d-flex justify-content-between align-items-center">
                <span>${escapeHtml(item.brand)} ${escapeHtml(
      item.model
    )} <small class="text-muted">(${escapeHtml(
      item.engine_number
    )})</small></span>
                <button class="btn btn-sm btn-outline-danger" onclick="undoAddToTransfer(${
                  item.id
                })"><i class="bi bi-x-lg"></i></button>
            </div>
        `);
  });

  if (totalInitial === 0 && itemsAddedCount === 0) {
    initialList.html(
      '<p class="text-muted text-center small p-3">No items in this transfer.</p>'
    );
  }

  
  $("#manageTransferTotal").text(totalInitial + itemsAddedCount);
  $("#manageTransferAdded").text(itemsAddedCount);
  $("#manageTransferRemoved").text(itemsRemovedCount);
}


function removeItemFromTransfer(motorcycleId) {
  if (!managingTransfer.itemsToRemove.includes(motorcycleId)) {
    managingTransfer.itemsToRemove.push(motorcycleId);
  }
  renderManagingTransferLists();
}

function undoRemoveFromTransfer(motorcycleId) {
  managingTransfer.itemsToRemove = managingTransfer.itemsToRemove.filter(
    (id) => id !== motorcycleId
  );
  renderManagingTransferLists();
}

function addItemToTransfer(id, brand, model, engine, cost) {
  const isAlreadyInitial = managingTransfer.initialItems.some(
    (item) => item.id === id
  );
  const isAlreadyAdded = managingTransfer.itemsToAdd.some(
    (item) => item.id === id
  );

  if (isAlreadyInitial || isAlreadyAdded) {
    showErrorModal("This motorcycle is already in the transfer list.");
    return;
  }

  managingTransfer.itemsToAdd.push({
    id,
    brand,
    model,
    engine_number: engine,
    inventory_cost: cost,
  });
  renderManagingTransferLists();
  $("#manageTransferSearch").val("").focus();
  $("#manageTransferSearchResults").empty();
}

function undoAddToTransfer(motorcycleId) {
  managingTransfer.itemsToAdd = managingTransfer.itemsToAdd.filter(
    (item) => item.id !== motorcycleId
  );
  renderManagingTransferLists();
}
