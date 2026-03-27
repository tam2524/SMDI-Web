$(document).ready(function() {
    
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
                { value: "inventory_movement", text: "Inventory Movement History" }
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
    $('.nav-link-custom').on('click', function(e) {
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
    }

    // Period Switcher
    $('#period').on('change', function() {
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
    $('#masterReportForm').on('submit', function(e) {
        e.preventDefault();
        generateReportPreview();
    });

    function generateReportPreview() {
        const formData = new FormData($('#masterReportForm')[0]);
        const type = $('#report_type').val();
        const period = $('#period').val();
        
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
            customer_name: $('#customer_search').val()
        };

        const btn = $('.btn-generate');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Generating...');

        $.ajax({
            url: '../api/reports_master.php',
            type: 'GET',
            data: params,
            success: function(response) {
                btn.prop('disabled', false).html('<i class="bi bi-play-circle"></i> Generate Report Preview');
                
                if (response.success) {
                    lastReportData = response.data;
                    lastReportConfig = response.config;
                    renderPreview(response.data, response.config, response.summary);
                } else {
                    showStatus('error', 'Failed to generate report', response.message);
                }
            },
            error: function() {
                btn.prop('disabled', false).html('<i class="bi bi-play-circle"></i> Generate Report Preview');
                showStatus('error', 'Connection Error', 'Please check your internet connection and try again.');
            }
        });
    }

    function renderPreview(data, config, summary) {
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
                rowHtml += `<td>${displayVal}</td>`;
            });
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

        $('#previewArea').fadeIn();
        $('html, body').animate({ scrollTop: $('#previewArea').offset().top - 100 }, 500);
    }

    // Print Logic
    $('#printReportBtn').on('click', function() {
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
        $('#printCriteria').text(`Branch: ${branch.toUpperCase()} | Period: ${date_value} | Generated on: ${new Date().toLocaleString()}`);
        
        window.print();
    });

    // Export Logic
    $('.export-btn').on('click', function() {
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
            ["ROXAS CITY SOLID MERCHANDISING"],
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
        doc.text('ROXAS CITY SOLID MERCHANDISING', 14, 20);

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
        
        $(container).find('input[type="date"], input[type="month"], input[type="datetime-local"]').each(function() {
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
});
