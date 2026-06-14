$(document).ready(function () {
    window.reportHeaderTitle = 'ROXAS CITY SOLID MERCHANDISING';
    $.get('../api/spareparts_inventory.php?action=get_user_info', function (res) {
        try {
            const data = typeof res === 'string' ? JSON.parse(res) : res;
            if (data && data.success && data.data.report_header_title) {
                window.reportHeaderTitle = data.data.report_header_title;
            }
        } catch (e) {}
    }, 'json');

    // Config for different report categories
    const reportConfigs = {
        inventory: {
            title: "Inventory Management Reports",
            desc: "Maintain optimal stock levels and prevent stock-outs.",
            options: [
                { value: "stock_status", text: "Stock Status / Valuation Report" },
                { value: "inventory_balance", text: "Inventory Balance (In/Out)" },
                { value: "reorder_point", text: "Reorder Point (To Buy List)" },
                { value: "inventory_aging", text: "Inventory Aging Report" },
                { value: "inventory_movement", text: "Inventory Movement History" },
                { value: "received_stocks_summary", text: "Summary of Received Stocks" },
                { value: "supplier_received_stocks", text: "Supplier Stock-In Summary (By Invoice/Date)" },
                { value: "transferred_stocks_summary", text: "Summary of Transferred Stocks" }
            ]
        },
        sales: {
            title: "Sales & Revenue Reports",
            desc: "Track immediate liquidity and future receivables.",
            options: [
                { value: "daily_sales_summary", text: "Daily Sales Summary (Cash vs Charge)" },
                { value: "sales_by_item", text: "Sales by Item / Category" },
                { value: "sales_per_employee", text: "Sales per Employee" },
                { value: "profit_margin", text: "Profit Margin Report" },
                { value: "branch_performance", text: "Branch Performance" }
            ]
        },
        payments: {
            title: "Accounts Receivable & Payments",
            desc: "Monitor collections and outstanding customer balances.",
            options: [
                { value: "ar_aging", text: "AR Aging Report" },
                { value: "customer_ledger", text: "Customer Statement / Ledger" },
                { value: "collection_report", text: "Collection Report" }
            ]
        },
        transfer: {
            title: "Transfer & Multi-Location",
            desc: "Manage stock movements between branches and warehouses.",
            options: [
                { value: "in_transit", text: "In-Transit Stocks Report" },
                { value: "transfer_history", text: "Transfer History" },
                { value: "stock_reconciliation", text: "Stock Reconciliation Report" }
            ]
        },
        payables: {
            title: "Accounts Payable & Supplier Aging",
            desc: "Monitor outstanding balances and payments to suppliers.",
            options: [
                { value: "supplier_aging", text: "Supplier Aging Report (Payables)" },
                { value: "supplier_payments", text: "Payment to Supplier History" }
            ]
        }
    };

    let currentCategory = 'inventory';

    // Check URL parameter for default category
    const urlParams = new URLSearchParams(window.location.search);
    const catParam = urlParams.get('category');
    if (catParam && reportConfigs[catParam]) {
        currentCategory = catParam;
        $('.nav-link-custom').removeClass('active');
        $(`.nav-link-custom[data-category="${currentCategory}"]`).addClass('active');

        const config = reportConfigs[currentCategory];
        $('#activeReportTitle').text(config.title);
        $('#activeReportDesc').text(config.desc);
    }

    let lastReportData = null;
    let lastReportConfig = null;

    // Load branches for admin
    if (window.isAdmin) {
        loadBranches();
    }

    function loadBranches() {
        $.get('../api/spareparts_inventory.php?action=get_branches', response => {
            if (response.success) {
                let html = '<option value="all">All Branches</option>';
                response.data.forEach(branch => {
                    html += `<option value="${branch}">${branch}</option>`;
                });
                $('#branch_filter').html(html);
            }
        });
    }

    // Initialize report options
    updateReportOptions(currentCategory);

    // Category Switcher
    $('.nav-link-custom').on('click', function (e) {
        e.preventDefault();
        const cat = $(this).data('category');
        currentCategory = cat;

        $('.nav-link-custom').removeClass('active');
        $(this).addClass('active');

        const config = reportConfigs[cat];
        $('#activeReportTitle').text(config.title);
        $('#activeReportDesc').text(config.desc);

        updateReportOptions(cat);
        $('#previewArea').fadeOut();
    });

    function updateReportOptions(cat) {
        const select = $('#report_type');
        select.empty();
        reportConfigs[cat].options.forEach(opt => {
            select.append(`<option value="${opt.value}">${opt.text}</option>`);
        });
        select.trigger('change');
    }

    $('#report_type').on('change', function () {
        const type = $(this).val();
        const refLabel = $('label[for="ref_no_search"]');
        const refInput = $('#ref_no_search');

        if (type === 'transferred_stocks_summary') {
            refLabel.text('Transfer Number');
            refInput.attr('placeholder', 'Enter Transfer # (ST...)');
        } else if (type === 'supplier_received_stocks' || type === 'received_stocks_summary') {
            refLabel.text('Invoice / DR Number');
            refInput.attr('placeholder', 'Enter Invoice #');
        } else {
            refLabel.text('Reference Number');
            refInput.attr('placeholder', 'Enter Reference #');
        }
    });

    // Period Switcher
    $('#period').on('change', function () {
        const val = $(this).val();
        $('.date-input-wrap').addClass('d-none');

        if (val === 'monthly') {
            $('#month_input_wrap').removeClass('d-none');
        } else if (val === 'daily') {
            $('#date_input_wrap').removeClass('d-none');
        } else {
            $('#custom_input_wrap').removeClass('d-none');
        }
    });

    // Form Submission
    $('#masterReportForm').on('submit', function (e) {
        e.preventDefault();
        generateReportPreview();
    });

    function generateReportPreview() {
        const formData = new FormData($('#masterReportForm')[0]);
        const type = $('#report_type').val();
        const period = $('#period').val();
        const customerVal = $('#customer_search').val().trim();

        if (type === 'customer_ledger' && !customerVal) {
            showStatus('warning', 'Missing Information', 'Please enter a Customer Name to generate the Customer Ledger preview.');
            return;
        }

        // Map UI values to API expected fields
        let date_value = '';
        if (period === 'monthly') date_value = $('#month_value').val();
        else if (period === 'daily') date_value = $('#date_value').val();
        else date_value = $('#start_date').val() + ' to ' + $('#end_date').val();

        const params = {
            action: 'generate_master_report',
            category: currentCategory,
            report_type: type,
            period: period,
            date_value: date_value,
            branch: $('#branch_filter').val(),
            brand: $('#brand_search').val(),
            part_no: $('#part_no_search').val(),
            ref_no: $('#ref_no_search').val(),
            customer_name: customerVal
        };

        const btn = $('.btn-generate');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Generating...');

        $.ajax({
            url: '../api/reports_master.php',
            type: 'GET',
            data: params,
            success: function (response) {
                btn.prop('disabled', false).html('<i class="bi bi-play-circle"></i> Generate Report Preview');

                if (response.success) {
                    lastReportData = response.data;
                    lastReportConfig = response.config;
                    if (response.report_header_title) {
                        window.reportHeaderTitle = response.report_header_title;
                    }
                    renderPreview(response.data, response.config, response.summary, response.customer_info);
                } else {
                    showStatus('error', 'Failed to generate report', response.message);
                }
            },
            error: function () {
                btn.prop('disabled', false).html('<i class="bi bi-play-circle"></i> Generate Report Preview');
                showStatus('error', 'Connection Error', 'Please check your internet connection and try again.');
            }
        });
    }

    function renderPreview(data, config, summary, customerInfo) {
        const thead = $('#previewThead');
        const tbody = $('#previewTbody');
        const tfoot = $('#previewTfoot');
        const summaryBox = $('#reportSummaryBox');

        thead.empty(); tbody.empty(); tfoot.empty(); summaryBox.empty();

        if (!data || data.length === 0) {
            tbody.html('<tr><td colspan="10" class="text-center py-5 text-muted">No records found for the selected criteria.</td></tr>');
            $('#recordsCount').text('Showing 0 records');
            $('#previewArea').fadeIn();
            return;
        }

        // Render Header
        let headerHtml = '<tr>';
        config.headers.forEach(h => headerHtml += `<th>${h}</th>`);
        const reportType = $('#report_type').val();
        const hasAction = (currentCategory === 'payments' && reportType === 'ar_aging') || 
                          (currentCategory === 'inventory' && (reportType === 'supplier_received_stocks' || reportType === 'transferred_stocks_summary' || reportType === 'received_stocks_summary'));
        if (hasAction) {
            headerHtml += '<th class="text-center d-print-none">Action</th>';
        }
        headerHtml += '</tr>';
        thead.append(headerHtml);

        // Render Body
        data.forEach(row => {
            let rowHtml = '<tr>';
            config.keys.forEach(key => {
                let cellVal = row[key];
                let displayVal = cellVal !== undefined && cellVal !== null ? cellVal : '-';

                if (config.formatters?.[key] === 'currency' && displayVal !== '-') {
                    displayVal = formatCurrency(displayVal);
                }
                let alignClass = '';
                if (config.formatters?.[key] === 'currency') alignClass = 'text-end';
                
                let extraClass = '';
                if (row.debit_credit_type === 'Grand Totals') {
                    if (key === 'debit_credit_type') extraClass = 'text-end fw-bold text-dark';
                    if (config.formatters?.[key] === 'currency') extraClass = 'fw-bold bg-light';
                    if (key === 'balance') extraClass += ' text-primary'; // To match blueish tint in photo
                }

                rowHtml += `<td class="${alignClass} ${extraClass}">${displayVal}</td>`;
            });

            if (currentCategory === 'payments' && $('#report_type').val() === 'ar_aging') {
                rowHtml += `<td class="text-center d-print-none"><button class="btn btn-sm btn-outline-success fw-bold" onclick="viewCustomerHistory('${row.customer_name.replace(/'/g, "\\'")}')"><i class="bi bi-eye"></i> Aging View</button></td>`;
            }
            if (currentCategory === 'inventory' && (reportType === 'supplier_received_stocks' || reportType === 'transferred_stocks_summary' || reportType === 'received_stocks_summary')) {
                const isTransfer = (reportType === 'transferred_stocks_summary') || (row.receive_type && row.receive_type.includes('TRANSFER'));
                const printFn = isTransfer ? 'printStockTransfer' : 'printSingleRR';
                rowHtml += `<td class="text-center d-print-none">
                                <button class="btn btn-sm btn-outline-danger fw-bold" onclick="${printFn}('${(row.reference || row.transfer_no || '').replace(/'/g, "\\'")}')">
                                    <i class="bi bi-printer"></i> Print
                                </button>
                            </td>`;
            }

            rowHtml += '</tr>';
            tbody.append(rowHtml);
        });

        // Records count
        $('#recordsCount').text(`Showing ${data.length} records`);

        // Summary Box
        if (summary && summary.length > 0) {
            summary.forEach(s => {
                summaryBox.append(`
                    <div class="text-center px-4 border-end last-child-border-0">
                        <div class="small text-muted text-uppercase fw-bold">${s.label}</div>
                        <div class="fs-4 fw-800 text-green-900">${s.format === 'currency' ? formatCurrency(s.value) : s.value}</div>
                    </div>
                `);
            });
        }

        // Set Print Titles so it's ready when user clicks Print This Preview
        let reportTitle = $('#report_type option:selected').text();
        if ($('#report_type').val() === 'received_stocks_summary') {
            reportTitle = 'STOCK TRANSFER (INCOMING)';
        }
        const branch = $('#branch_filter').val();
        const period = $('#period').val();

        if (customerInfo && Object.keys(customerInfo).length > 0) {
            const limitStr = '₱' + parseFloat(customerInfo.credit_limit || 0).toLocaleString(undefined, { minimumFractionDigits: 2 });
            $('#customerInfoPrintWrap').html(`
                <table style="width: 100%; border: none; margin-top: 20px;">
                    <tr>
                        <td style="border: none !important; width: 60%; vertical-align: top; padding: 0;">
                            <div style="font-size: 8pt; font-weight: bold; color: #555;">CUSTOMER INFORMATION:</div>
                            <div style="font-size: 11pt; font-weight: 800; text-transform: uppercase;">${customerInfo.name}</div>
                            <div style="font-size: 9pt; color: #555;">${customerInfo.address || ''}</div>
                            <div style="font-size: 9pt; color: #555;">${customerInfo.contact || ''}</div>
                        </td>
                        <td style="border: none !important; width: 40%; vertical-align: bottom; text-align: right; padding: 0;">
                            <div style="margin-bottom: 5px;">
                                <span style="font-size: 8pt; font-weight: bold; color: #555;">RANK LEVEL:</span> 
                                <span style="font-size: 10pt; font-weight: 800; margin-left: 10px;">${customerInfo.rank}</span>
                            </div>
                            <div>
                                <span style="font-size: 8pt; font-weight: bold; color: #555;">CREDIT LIMIT:</span> 
                                <span style="font-size: 10pt; font-weight: 800; margin-left: 10px;">${limitStr}</span>
                            </div>
                        </td>
                    </tr>
                </table>
            `).removeClass('d-none');
            $('#printTitle').text('STATEMENT OF ACCOUNT');
            $('#screenReportTitle').text('STATEMENT OF ACCOUNT');
        } else {
            $('#customerInfoPrintWrap').addClass('d-none').empty();
            $('#printTitle').text(reportTitle.toUpperCase());
            $('#screenReportTitle').text(reportTitle.toUpperCase());
        }
        const criteriaText = `Generated on: ${new Date().toLocaleString()} | Branch: ${branch.toUpperCase()}`;
        $('#printCriteria').text(criteriaText);
        $('#screenReportCriteria').text(criteriaText);
        
        const companyHeaderVal = window.reportHeaderTitle || 'ROXAS CITY SOLID MERCHANDISING';
        $('#printCompanyHeader').text(companyHeaderVal);
        $('#screenCompanyHeader').text(companyHeaderVal);

        // Update footer based on category
        if (currentCategory === 'payments' || currentCategory === 'sales') {
            $('#notedByLabel').text('NOTED / RECEIVED BY');
            $('#notedBySub').text('Customer / Representative');
            $('#footerDisclaimerText').text('This is a system-generated statement. Please settle outstanding balances to maintain your good credit standing.');
        } else if (currentCategory === 'payables') {
            $('#notedByLabel').text('NOTED / APPROVED BY');
            $('#notedBySub').text('Management / Finance');
            $('#footerDisclaimerText').text('This is a system-generated report for payables and disbursements.');
        } else if (currentCategory === 'inventory' || currentCategory === 'transfer') {
            $('#notedByLabel').text('VERIFIED BY');
            $('#notedBySub').text('Warehouse / Inventory Manager');
            $('#footerDisclaimerText').text('This is a system-generated inventory report.');
        } else {
            $('#notedByLabel').text('NOTED / CHECKED BY');
            $('#notedBySub').text('Manager / Supervisor');
        }

        // Apply Landscape dynamically for AR Aging during Preview render so it's ready when printed
        if ($('#report_type').val() === 'ar_aging' || $('#report_type').val() === 'inventory_aging') {
            $('#dynamicPrintOrientation').text('@media print { @page { size: landscape !important; margin: 10mm; } }');
        } else {
            $('#dynamicPrintOrientation').text('@media print { @page { size: portrait !important; margin: 15mm 10mm; } }');
        }

        $('#previewArea').fadeIn();
        $('html, body').animate({ scrollTop: $('#previewArea').offset().top - 100 }, 500);
    }

    // Print Logic
    $('#printReportBtn').on('click', function () {
        if (!lastReportData || lastReportData.length === 0) {
            showStatus('warning', 'No data to print', 'Please generate a report preview first.');
            return;
        }

        const reportTitle = $('#report_type option:selected').text();
        const branch = $('#branch_filter').val();
        const period = $('#period').val();
        let date_value = '';
        if (period === 'monthly') date_value = $('#month_value').val();
        else if (period === 'daily') date_value = $('#date_value').val();
        else date_value = $('#start_date').val() + ' to ' + $('#end_date').val();

        $('#printTitle').text(reportTitle.toUpperCase());
        $('#printCriteria').text(`Generated on: ${new Date().toLocaleString()} | Branch: ${branch.toUpperCase()}`);
        $('#printCompanyHeader').text(window.reportHeaderTitle || 'ROXAS CITY SOLID MERCHANDISING');

        // Update footer based on category
        if (currentCategory === 'payments' || currentCategory === 'sales') {
            $('#notedByLabel').text('NOTED / RECEIVED BY');
            $('#notedBySub').text('Customer / Representative');
            $('#footerDisclaimerText').text('This is a system-generated statement. Please settle outstanding balances to maintain your good credit standing.');
        } else if (currentCategory === 'payables') {
            $('#notedByLabel').text('NOTED / APPROVED BY');
            $('#notedBySub').text('Management / Finance');
            $('#footerDisclaimerText').text('This is a system-generated report for payables and disbursements.');
        } else if (currentCategory === 'inventory' || currentCategory === 'transfer') {
            $('#notedByLabel').text('VERIFIED BY');
            $('#notedBySub').text('Warehouse / Inventory Manager');
            $('#footerDisclaimerText').text('This is a system-generated inventory report.');
        } else {
            $('#notedByLabel').text('NOTED / CHECKED BY');
            $('#notedBySub').text('Manager / Supervisor');
            $('#footerDisclaimerText').text('This is a system-generated report.');
        }

        window.print();
    });

    // Disable any dynamically injected event handlers for inline preview print
    $(document).off('click', 'button[onclick="window.print()"]');

    // Export Logic
    $('.export-btn').on('click', function () {
        if (!lastReportData || lastReportData.length === 0) {
            showStatus('warning', 'No data to export', 'Please generate a report preview first.');
            return;
        }

        const type = $(this).data('type');
        const reportTitle = $('#report_type option:selected').text();

        if (type === 'excel') exportToExcel(lastReportData, lastReportConfig, reportTitle);
        else exportToPDF(lastReportData, lastReportConfig, reportTitle);
    });

    function exportToExcel(data, config, title) {
        const worksheetData = [
            [window.reportHeaderTitle || "ROXAS CITY SOLID MERCHANDISING"],
            ["SPARE PARTS MANAGEMENT SYSTEM"],
            [`REPORT: ${title.toUpperCase()}`],
            [`Branch: ${$('#branch_filter').val().toUpperCase()} | Generated: ${new Date().toLocaleString()}`],
            [],
            config.headers,
            ...data.map(row => config.keys.map(key => {
                let val = row[key];
                if (val === undefined || val === null) return '-';
                if (config.formatters?.[key] === 'currency') {
                    return parseFloat(val) || 0; // Excel handles numbers better
                }
                return val;
            }))
        ];

        const worksheet = XLSX.utils.aoa_to_sheet(worksheetData);
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, "Report");
        XLSX.writeFile(workbook, `${title.replace(/\s+/g, '_')}_${new Date().toISOString().split('T')[0]}.xlsx`);
    }

    function exportToPDF(data, config, title) {
        // Find jsPDF constructor (handle different UMD load variations)
        const jsPDF = window.jspdf ? window.jspdf.jsPDF : window.jsPDF;
        if (!jsPDF) {
            showStatus('error', 'PDF Error', 'PDF library not loaded correctly.');
            return;
        }

        const doc = new jsPDF('l', 'mm', 'a4');

        // Ensure autoTable plugin is registered on this instance
        if (typeof doc.autoTable !== 'function') {
            showStatus('error', 'PDF Error', 'AutoTable plugin not detected.');
            return;
        }

        doc.setFontSize(22);
        doc.setTextColor(0, 61, 51); // --green-900
        doc.text(window.reportHeaderTitle || 'ROXAS CITY SOLID MERCHANDISING', 14, 20);

        doc.setFontSize(11);
        doc.setTextColor(100);
        doc.text('Spare Parts Management System', 14, 28);
        doc.text(`REPORT: ${title.toUpperCase()}`, 14, 34);
        doc.text(`Generated on: ${new Date().toLocaleString()}`, 14, 40);
        doc.text(`Branch: ${$('#branch_filter').val().toUpperCase()}`, 14, 46);
        doc.setDrawColor(0, 61, 51);
        doc.line(14, 50, 283, 50);

        const body = data.map(row => config.keys.map(key => {
            let val = row[key];
            if (val === undefined || val === null) return '-';
            if (config.formatters?.[key] === 'currency') return formatCurrency(val);
            return val;
        }));

        doc.autoTable({
            head: [config.headers],
            body: body,
            startY: 56,
            theme: 'grid',
            headStyles: { fillColor: [0, 77, 64], textColor: 255 },
            styles: { fontSize: 8 },
            margin: { top: 56 }
        });

        doc.save(`${title.replace(/\s+/g, '_')}_${new Date().toISOString().split('T')[0]}.pdf`);
    }

    // Helper Functions
    function formatCurrency(amount) {
        if (amount === '-' || amount === null || amount === undefined) return '-';
        const num = parseFloat(amount);
        if (isNaN(num)) return amount;
        return '₱' + num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // Auto-fill all date/month pickers with today's date
    function autoFillDates(container = document) {
        const today = new Date();
        const yyyy = today.getFullYear();
        let mm = today.getMonth() + 1;
        let dd = today.getDate();
        if (dd < 10) dd = '0' + dd;
        if (mm < 10) mm = '0' + mm;
        const formattedDate = `${yyyy}-${mm}-${dd}`;
        const formattedMonth = `${yyyy}-${mm}`;

        $(container).find('input[type="date"], input[type="month"], input[type="datetime-local"]').each(function () {
            if (!$(this).val()) {
                if ($(this).attr('type') === 'date') {
                    $(this).val(formattedDate);
                } else if ($(this).attr('type') === 'month') {
                    $(this).val(formattedMonth);
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

    // Initial call
    autoFillDates();
    $(document).on('shown.bs.modal', function (e) {
        autoFillDates(e.target);
    });

    function showStatus(type, title, msg) {
        const icon = $('#statusIcon');
        icon.empty();

        if (type === 'success') icon.append('<i class="bi bi-check-circle text-success fs-1"></i>');
        else if (type === 'error') icon.append('<i class="bi bi-x-circle text-danger fs-1"></i>');
        else icon.append('<i class="bi bi-exclamation-triangle text-warning fs-1"></i>');

        $('#statusTitle').text(title);
        $('#statusMsg').text(msg);
        new bootstrap.Modal('#statusModal').show();
    }

    window.viewCustomerHistory = function (customerName) {
        const period = $('#period').val();
        let date_value = '';
        if (period === 'monthly') date_value = $('#month_value').val();
        else if (period === 'daily') date_value = $('#date_value').val();
        else date_value = $('#start_date').val() + ' to ' + $('#end_date').val();

        $('#ledgerCustomerName').text(customerName.toUpperCase());
        $('#ledgerPeriods').text(`Transaction History | ${date_value}`);

        $('#printLedgerCustomerName').text(customerName.toUpperCase());
        $('#printLedgerPeriods').text(`Transaction History | ${date_value}`);

        let now = new Date();
        $('#printLedgerDateFooter').text(now.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: '2-digit' }));
        $('#printLedgerTimeFooter').text(now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }));

        $('#ledgerModalTbody').html('<tr><td colspan="6" class="text-center py-5"><div class="spinner-border spinner-border-sm me-2"></div>Loading ledger...</td></tr>');

        const modal = new bootstrap.Modal('#customerLedgerModal');
        modal.show();

        const params = {
            action: 'generate_master_report',
            category: 'payments',
            report_type: 'customer_ledger',

            period: period,
            date_value: date_value,
            branch: $('#branch_filter').val(),
            customer_name: customerName
        };

        $.get('../api/reports_master.php', params, function (response) {
            if (response.success && response.report_header_title) {
                window.reportHeaderTitle = response.report_header_title;
            }
            if (response.success && response.data.length > 0) {
                let html = '';
                let totalDebit = 0;
                let totalCredit = 0;
                let finalBalance = 0;

                response.data.forEach(row => {
                    const debit = parseFloat(row.debit || 0);
                    const credit = parseFloat(row.credit || 0);
                    const balance = parseFloat(row.balance || 0);

                    totalDebit += debit;
                    totalCredit += credit;
                    finalBalance = balance;

                    html += `<tr>
                                <td>${row.date}</td>
                                <td><code class="text-dark">${row.ref || '-'}</code></td>
                                <td>${row.debit_credit_type}</td>
                                <td class="text-end text-danger">${debit > 0 ? formatCurrency(debit) : '-'}</td>
                                <td class="text-end text-success">${credit > 0 ? formatCurrency(credit) : '-'}</td>
                                <td class="text-end fw-bold">${formatCurrency(balance)}</td>
                             </tr>`;
                });

                // Summary Row
                html += `<tr class="table-light shadow-sm">
                    <td colspan="3" class="text-end fw-bold py-3 text-uppercase border-top-2">Grand Totals</td>
                    <td class="text-end fw-bold text-danger border-top-2">${formatCurrency(totalDebit)}</td>
                    <td class="text-end fw-bold text-success border-top-2">${formatCurrency(totalCredit)}</td>
                    <td class="text-end fw-bold text-primary border-top-2" style="background: #f0faff;">${formatCurrency(finalBalance)}</td>
                </tr>`;

                $('#ledgerModalTbody').html(html);
            } else {
                $('#ledgerModalTbody').html('<tr><td colspan="6" class="text-center py-5 text-muted">No transactions found for this period.</td></tr>');
            }
        });
    };

    window.printLedger = function () {
        const customerName = $('#printLedgerCustomerName').text();
        const period = $('#printLedgerPeriods').text();
        const tbodyHtml = $('#ledgerModalTbody').html();
        const branch = window.currentBranch || 'HEADOFFICE';
        const dateNow = new Date().toLocaleString();

        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
        <html>
        <head>
            <title>Statement of Account - ${customerName}</title>
            <style>
                @page {
                    size: portrait;
                    margin: 15mm 10mm 20mm 10mm;
                    @bottom-center {
                        content: "Page " counter(page);
                        font-size: 8pt;
                        font-family: 'Segoe UI', Arial, sans-serif;
                        color: #555;
                    }
                }
                body { font-family: 'Segoe UI', Arial, sans-serif; color: #000; margin: 20px; font-size: 10pt; }
                .report-header-print { text-align: center; border-bottom: 3px double #004d40; padding-bottom: 15px; margin-bottom: 25px; display: block; width: 100%; }
                .report-header-print .company-name { font-size: 18pt; font-weight: 800; color: #004d40; margin-bottom: 2px; text-transform: uppercase; letter-spacing: 1px; }
                .report-header-print .company-address { font-size: 9pt; color: #444; margin-bottom: 2px; }
                .report-header-print .system-name { font-size: 9pt; font-weight: 600; color: #666; font-style: italic; }
                .report-title-container { margin-top: 15px; }
                .report-title { font-size: 14pt; font-weight: 700; color: #000; text-decoration: underline; text-transform: uppercase; margin-bottom: 5px; }
                .report-timestamp { font-size: 9pt; color: #777; font-style: italic; }
                
                .customer-info-box { margin-bottom: 15px; width: 100%; display: table; }
                .customer-info-left { display: table-cell; width: 100%; }
                .info-label { font-size: 8pt; color: #555; text-transform: uppercase; font-weight: bold; }
                .info-value { font-size: 11pt; font-weight: 700; text-transform: uppercase; color: #000; margin-bottom: 3px; }
                
                table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                th { background-color: #f2f2f2 !important; color: #000 !important; font-size: 9pt; font-weight: 700; text-transform: uppercase; border: 1px solid #333 !important; padding: 8px; text-align: left; }
                th.text-end, td.text-end { text-align: right !important; }
                td { font-size: 9pt; vertical-align: middle; border: 1px solid #ddd !important; padding: 6px 8px; }
                td code { border: none !important; padding: 0 !important; font-weight: bold; background: none !important; color: #000 !important; font-family: inherit; }
                
                .sig-section { margin-top: 25px; display: flex; width: 100%; }
                .sig-box { flex: 1; text-align: center; }
                .sig-line { border-bottom: 1.5px solid #000; width: 80%; margin: 30px auto 5px auto; }
                .sig-label { font-size: 8pt; font-weight: bold; text-transform: uppercase; color: #555; }
                .footer-stamp { text-align: center; font-size: 8pt; font-style: italic; color: #777; margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px; }
                .sig-and-footer-wrap { page-break-inside: avoid !important; break-inside: avoid !important; page-break-before: avoid !important; break-before: avoid !important; }
                
                @media print {
                    * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                }
            </style>
        </head>
        <body onload="window.print();">
            <div class="report-header-print">
                <div class="company-name">${window.reportHeaderTitle || 'ROXAS CITY SOLID MERCHANDISING'}</div>
                <div class="system-name">Spareparts Management System</div>
                <div class="report-title-container">
                    <div class="report-title">STATEMENT OF ACCOUNT</div>
                    <div class="report-timestamp">Generated on: ${dateNow} | Branch: ${branch}</div>
                </div>
            </div>
            
            <div class="customer-info-box">
                <div class="customer-info-left">
                    <div class="info-label">Customer Information:</div>
                    <div class="info-value">${customerName}</div>
                    <div style="font-size: 9pt; color: #444;">${period}</div>
                </div>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th class="ps-3">Date</th>
                        <th>Reference #</th>
                        <th>Transaction Info</th>
                        <th class="text-end">Debit (Charge)</th>
                        <th class="text-end">Credit (Payment)</th>
                        <th class="text-end pe-3">Running Balance</th>
                    </tr>
                </thead>
                <tbody>
                    ${tbodyHtml}
                </tbody>
            </table>
            
            <div class="sig-and-footer-wrap">
                <div class="sig-section">
                    <div class="sig-box">
                        <div class="sig-line"></div>
                        <div class="sig-label">Prepared By</div>
                        <div style="font-size: 7.5pt; color: #777;">Authorized Personnel</div>
                    </div>
                    <div class="sig-box">
                        <div class="sig-line"></div>
                        <div class="sig-label">Noted / Received By</div>
                        <div style="font-size: 7.5pt; color: #777;">Customer / Representative</div>
                    </div>
                </div>
                
                <div class="footer-stamp">
                    This is a system-generated statement. Please settle outstanding balances to maintain your good credit standing.
                </div>
            </div>
        </body>
        </html>
        `);
        printWindow.document.close();
    };

    window.printSingleRR = function(reference) {
        if (!lastReportData) return;
        
        // Filter items with the same reference
        const items = lastReportData.filter(i => (i.reference === reference || i.transfer_no === reference));
        if (items.length === 0) return;
        
        const first = items[0];
        const supplier = first.supplier || first.source_branch || 'N/A';
        const date = first.transaction_date;
        const branch = first.receiving_branch || 'ROXAS';
        const paymentMode = first.payment_method || 'N/A';
        const isTransfer = !!first.source_branch;
        const reportTitle = isTransfer ? 'TRANSFER REPORT (RR/OUT)' : 'RECEIVING REPORT (RR/IN)';
        const dateNow = new Date().toLocaleString();
        
        let totalQty = 0;
        let totalValue = 0;
        let rowsHtml = '';
        
        items.forEach(item => {
            let qty = parseInt(item.quantity) || 0;
            let cost = parseFloat(item.unit_cost) || 0;
            let subtotal = parseFloat(item.total_amount) || 0;
            totalQty += qty;
            totalValue += subtotal;
            
            rowsHtml += `
                <tr>
                    <td>
                        <div style="font-weight:bold; text-transform:lowercase">${item.description}</div>
                        <div style="font-size:8pt;color:#777; font-style:italic">${item.part_no}</div>
                    </td>
                    <td style="text-align:center; color:#1a237e; font-weight:bold">${qty}</td>
                    <td style="text-align:right">₱${cost.toLocaleString(undefined, {minimumFractionDigits:2})}</td>
                    <td style="text-align:right;font-weight:bold">₱${subtotal.toLocaleString(undefined, {minimumFractionDigits:2})}</td>
                </tr>
            `;
        });

        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
            <head>
                <title>Receiving Report - ${reference}</title>
                <style>
                    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800&display=swap');
                    @page {
                        size: portrait;
                        margin: 15mm 10mm 20mm 10mm;
                        @bottom-center {
                            content: "Page " counter(page);
                            font-size: 8pt;
                            font-family: 'Inter', sans-serif;
                            color: #555;
                        }
                    }
                    body { font-family: 'Inter', 'Segoe UI', sans-serif; color: #333; margin: 20px; font-size: 10pt; }
                    
                    .report-header { text-align: center; border-bottom: 3px double #004d40; padding-bottom: 15px; margin-bottom: 25px; }
                    .company-name { font-size: 20pt; font-weight: 800; color: #004d40; margin-bottom: 2px; text-transform: uppercase; letter-spacing: 1px; }
                    .system-name { font-size: 10pt; font-weight: 600; color: #666; font-style: italic; }
                    
                    .report-title-box { margin-top: 15px; text-align: center; }
                    .report-title { font-size: 16pt; font-weight: 700; color: #000; text-decoration: underline; text-transform: uppercase; margin-bottom: 5px; }
                    .report-meta { font-size: 9pt; color: #777; font-style: italic; }
                    
                    .info-grid { margin-top: 30px; margin-bottom: 25px; width: 100%; display: table; }
                    .info-col { display: table-cell; width: 50%; }
                    .info-label { font-size: 8pt; color: #555; text-transform: uppercase; font-weight: bold; margin-bottom: 2px; }
                    .info-value { font-size: 12pt; font-weight: 800; text-transform: uppercase; color: #000; margin-bottom: 10px; }
                    .highlight-red { color: #d32f2f !important; }
                    
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; border: 1.5px solid #333; }
                    th { background-color: #f8f9fa !important; color: #000; font-size: 9pt; font-weight: 700; text-transform: uppercase; border: 1.5px solid #333; padding: 10px; text-align: left; }
                    td { font-size: 10pt; vertical-align: middle; border: 1px solid #ccc; padding: 8px 12px; }
                    
                    .total-row { background-color: #fdfdfd; font-weight: 800; }
                    .total-label { text-align: right; text-transform: uppercase; padding-right: 20px; }
                    
                    .sig-container { margin-top: 35px; display: table; width: 100%; }
                    .sig-box { display: table-cell; width: 33.33%; text-align: center; }
                    .sig-line { border-bottom: 1.5px solid #000; width: 85%; margin: 0 auto 8px auto; }
                    .sig-text { font-size: 8.5pt; font-weight: 700; text-transform: uppercase; color: #444; }
                    
                    .footer-note { text-align: center; font-size: 8.5pt; font-style: italic; color: #888; margin-top: 25px; border-top: 1px solid #eee; padding-top: 15px; }
                    .sig-and-footer-wrap { page-break-inside: avoid !important; break-inside: avoid !important; page-break-before: avoid !important; break-before: avoid !important; }
                    
                    @media print {
                        * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                        body { margin: 0; }
                    }
                </style>
            </head>
            <body onload="window.print();">
                <div class="report-header">
                    <div class="company-name">${window.reportHeaderTitle || 'ROXAS CITY SOLID MERCHANDISING'}</div>
                    <div class="system-name">Spareparts Management System</div>
                    <div class="report-title-box">
                        <div class="report-title">${reportTitle}</div>
                        <div class="report-meta">Generated on: ${dateNow} | Branch: ${branch}</div>
                    </div>
                </div>
                
                <div class="info-grid">
                    <div class="info-col">
                        <div class="info-label">${isTransfer ? 'Source Branch' : 'Supplier'}:</div>
                        <div class="info-value">${supplier}</div>
                        <div class="info-label">Date ${isTransfer ? 'Transferred' : 'Received'}:</div>
                        <div class="info-value" style="font-size:11pt">${date}</div>
                    </div>
                    <div class="info-col" style="text-align: right;">
                        <div class="info-label">${isTransfer ? 'Transfer #' : 'Invoice / DR #'}:</div>
                        <div class="info-value highlight-red" style="font-size:16pt">${first.transfer_no || reference}</div>
                        ${isTransfer ? '' : `
                            <div class="info-label">Payment Mode:</div>
                            <div class="info-value" style="font-size:11pt">${paymentMode}</div>
                        `}
                    </div>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th style="width:55%">Item Details</th>
                            <th style="text-align:center; width:10%">Qty</th>
                            <th style="text-align:right; width:15%">Unit Cost</th>
                            <th style="text-align:right; width:20%">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rowsHtml}
                        <tr class="total-row">
                            <td class="total-label">Grand Totals</td>
                            <td style="text-align:center; color:#1a237e">${totalQty}</td>
                            <td></td>
                            <td style="text-align:right">₱${totalValue.toLocaleString(undefined, {minimumFractionDigits:2})}</td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="sig-and-footer-wrap">
                    <div class="sig-container">
                        <div class="sig-box">
                            <div class="sig-line"></div>
                            <div class="sig-text">Prepared By</div>
                        </div>
                        <div class="sig-box">
                            <div class="sig-line"></div>
                            <div class="sig-text">Verified By</div>
                        </div>
                        <div class="sig-box">
                            <div class="sig-line"></div>
                            <div class="sig-text">Approved By</div>
                        </div>
                    </div>
                    
                    <div class="footer-note">
                        This is a system-generated document and does not require a physical signature for validity.
                    </div>
                </div>
            </body>
            </html>
        `);
        printWindow.document.close();
    };

    window.printStockTransfer = function(reference) {
        if (!lastReportData) return;
        
        const items = lastReportData.filter(i => (i.transfer_no === reference || i.reference === reference));
        if (items.length === 0) return;
        
        const first = items[0];
        const origin = first.source_branch || 'ROXAS';
        const destination = first.receiving_branch || 'N/A';
        const displayRef = reference;
        const dateNow = new Date().toLocaleString();
        
        // Detect if Incoming or Outgoing
        const reportType = $('#report_type').val();
        let reportTitle = "STOCK TRANSFER";
        if (reportType === 'transferred_stocks_summary') {
            reportTitle = "STOCK TRANSFER (OUTGOING)";
        } else if (reportType === 'received_stocks_summary' || (first.receive_type && first.receive_type.includes('TRANSFER'))) {
            reportTitle = "STOCK TRANSFER (INCOMING)";
        }
        
        let rowsHtml = '';
        items.forEach(item => {
            rowsHtml += `
                <tr>
                    <td style="padding: 12px; border: 1px solid #ccc;">${item.part_no}</td>
                    <td style="padding: 12px; border: 1px solid #ccc;">${item.description}</td>
                    <td style="padding: 12px; border: 1px solid #ccc; text-align: center; font-weight: bold;">${item.quantity}</td>
                </tr>
            `;
        });

        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
            <head>
                <title>${reportTitle} - ${reference}</title>
                <style>
                    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap');
                    @page {
                        size: portrait;
                        margin: 15mm 10mm 20mm 10mm;
                        @bottom-center {
                            content: "Page " counter(page);
                            font-size: 8pt;
                            font-family: 'Inter', sans-serif;
                            color: #555;
                        }
                    }
                    body { font-family: 'Inter', sans-serif; color: #333; margin: 20px; font-size: 10pt; }
                    
                    .header { text-align: center; margin-bottom: 20px; }
                    .company { font-size: 18pt; font-weight: 800; color: #004d40; text-transform: uppercase; margin-bottom: 2px; }
                    .sub-system { font-size: 9pt; color: #666; font-style: italic; }
                    
                    .title-section { text-align: center; margin-top: 15px; border-top: 2px solid #004d40; padding-top: 15px; margin-bottom: 25px; }
                    .report-title { font-size: 14pt; font-weight: 700; text-decoration: underline; text-transform: uppercase; }
                    .gen-date { font-size: 8pt; color: #888; margin-top: 5px; }
                    
                    .meta-info { width: 100%; margin-bottom: 25px; display: table; border-bottom: 1px solid #eee; padding-bottom: 15px; }
                    .meta-col { display: table-cell; vertical-align: top; }
                    .meta-row { margin-bottom: 5px; }
                    .meta-label { font-weight: bold; width: 120px; display: inline-block; font-size: 8pt; color: #555; }
                    .meta-value { font-weight: 800; font-size: 10pt; text-transform: uppercase; }
                    
                    table { width: 100%; border-collapse: collapse; margin-top: 10px; border: 1.5px solid #333; }
                    th { background: #fdfdfd; border: 1px solid #333; padding: 10px; text-align: left; font-size: 9pt; text-transform: uppercase; font-weight: 800; }
                    
                    .footer-sigs { margin-top: 30px; width: 100%; display: table; }
                    .sig-box { display: table-cell; width: 50%; text-align: center; }
                    .sig-line { border-bottom: 1.5px solid #333; width: 80%; margin: 0 auto 5px auto; }
                    .sig-label { font-size: 8pt; font-weight: bold; text-transform: uppercase; color: #666; }
                    
                    .footer-note { text-align: center; font-size: 7.5pt; color: #999; margin-top: 30px; }
                    .sig-and-footer-wrap { page-break-inside: avoid !important; break-inside: avoid !important; page-break-before: avoid !important; break-before: avoid !important; }
                    
                    @media print {
                        * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                    }
                </style>
            </head>
            <body onload="window.print();">
                <div class="header">
                    <div class="company">${window.reportHeaderTitle || 'ROXAS CITY SOLID MERCHANDISING'}</div>
                    <div class="sub-system">Spareparts Management System</div>
                </div>
                
                <div class="title-section">
                    <div class="report-title">${reportTitle}</div>
                    <div class="gen-date">Generated on: ${new Date().toLocaleString()}</div>
                </div>
                
                <div class="meta-info">
                    <div class="meta-col" style="width: 60%;">
                        <div class="meta-row"><span class="meta-label">TRANSFER #:</span> <span class="meta-value">${displayRef}</span></div>
                        <div class="meta-row"><span class="meta-label">ORIGIN:</span> <span class="meta-value">${origin}</span></div>
                        <div class="meta-row"><span class="meta-label">DESTINATION:</span> <span class="meta-value">${destination}</span></div>
                    </div>
                    <div class="meta-col" style="width: 40%; text-align: right;">
                        <div class="meta-label">PRINT DATE:</div>
                        <div class="meta-value" style="font-size: 9pt;">${dateNow}</div>
                    </div>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>PART NUMBER</th>
                            <th>DESCRIPTION</th>
                            <th style="text-align: center; width: 20%;">QTY TRANSFERRED</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rowsHtml}
                    </tbody>
                </table>
                
                <div class="sig-and-footer-wrap">
                    <div class="footer-sigs">
                        <div class="sig-box">
                            <div class="sig-line" style="margin-top: 30px;"></div>
                            <div class="sig-label">RELEASED BY</div>
                        </div>
                        <div class="sig-box">
                            <div class="sig-line" style="margin-top: 30px;"></div>
                            <div class="sig-label">RECEIVED / ACCEPTED BY</div>
                        </div>
                    </div>
                    
                    <div class="footer-note">
                        Generated by Spare Parts Management System © 2026
                    </div>
                </div>
            </body>
            </html>
        `);
        printWindow.document.close();
    };
});
