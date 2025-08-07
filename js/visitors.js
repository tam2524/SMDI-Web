
// Visitor Logs Tab Functionality
$(document).ready(function() {
    let visitorCurrentPage = 1;
    let visitorTotalPages = 1;
    let visitorStartDate = '';
    let visitorEndDate = '';

    // Load visitor logs when tab is shown
    $('#visitors-tab').on('click', function() {
        loadVisitorStats();
        loadVisitorLogs();
    });

    // Load visitor statistics
    function loadVisitorStats() {
        $.ajax({
            url: '../api/visitor_stats.php',
            method: 'GET',
            data: { 
                start_date: visitorStartDate,
                end_date: visitorEndDate 
            },
            dataType: 'json',
            success: function(response) {
                $('#totalVisits').text(response.total_visits);
                $('#uniqueVisitors').text(response.unique_visitors);
                $('#todayVisits').text(response.today_visits);
                $('#monthVisits').text(response.month_visits);
            },
            error: function(xhr, status, error) {
                console.error("Error loading visitor stats:", error);
            }
        });
    }

    // Load visitor logs
    function loadVisitorLogs(page = 1) {
        $.ajax({
            url: '../api/visitor_logs.php',
            method: 'GET',
            data: { 
                page: page,
                start_date: visitorStartDate,
                end_date: visitorEndDate 
            },
            dataType: 'json',
            success: function(response) {
                $('#visitorsTableBody').empty();
                
                if (response.logs && response.logs.length > 0) {
                    response.logs.forEach(function(log) {
                        // Simple device detection from user agent
                        let device = 'Desktop';
                        if (/mobile/i.test(log.user_agent)) {
                            device = 'Mobile';
                        } else if (/tablet/i.test(log.user_agent)) {
                            device = 'Tablet';
                        } else if (/bot|spider|crawl/i.test(log.user_agent)) {
                            device = 'Bot';
                        }
                        
                        $('#visitorsTableBody').append(`
                            <tr>
                                <td>${log.ip_address}</td>
                                <td>${log.page_visited}</td>
                                <td>${device}</td>
                                <td>${new Date(log.visit_time).toLocaleString()}</td>
                            </tr>
                        `);
                    });
                    
                    visitorCurrentPage = response.current_page;
                    visitorTotalPages = response.total_pages;
                    updateVisitorPagination();
                } else {
                    $('#visitorsTableBody').append('<tr><td colspan="5" class="text-center">No visitor logs found</td></tr>');
                }
            },
            error: function(xhr, status, error) {
                console.error("Error loading visitor logs:", error);
            }
        });
    }

    // Update visitor pagination controls
    function updateVisitorPagination() {
        let paginationHtml = '';
        
        // Previous button
        paginationHtml += `<li class="page-item ${visitorCurrentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" id="prevVisitorsPage">Previous</a>
        </li>`;
        
        // Page numbers
        const startPage = Math.max(1, visitorCurrentPage - 2);
        const endPage = Math.min(visitorTotalPages, visitorCurrentPage + 2);
        
        if (startPage > 1) {
            paginationHtml += `<li class="page-item"><a class="page-link visitor-page-number" href="#" data-page="1">1</a></li>`;
            if (startPage > 2) {
                paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            paginationHtml += `<li class="page-item ${i === visitorCurrentPage ? 'active' : ''}">
                <a class="page-link visitor-page-number" href="#" data-page="${i}">${i}</a>
            </li>`;
        }
        
        if (endPage < visitorTotalPages) {
            if (endPage < visitorTotalPages - 1) {
                paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
            paginationHtml += `<li class="page-item"><a class="page-link visitor-page-number" href="#" data-page="${visitorTotalPages}">${visitorTotalPages}</a></li>`;
        }
        
        // Next button
        paginationHtml += `<li class="page-item ${visitorCurrentPage === visitorTotalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" id="nextVisitorsPage">Next</a>
        </li>`;
        
        $('#visitorsPaginationControls').html(paginationHtml);
    }

    // Filter visitors by date range
    $('#filterVisitorsBtn').on('click', function() {
        visitorStartDate = $('#visitorStartDate').val();
        visitorEndDate = $('#visitorEndDate').val();
        visitorCurrentPage = 1;
        loadVisitorStats();
        loadVisitorLogs();
    });

    // Reset visitor filter
    $('#resetVisitorFilterBtn').on('click', function() {
        $('#visitorStartDate').val('');
        $('#visitorEndDate').val('');
        visitorStartDate = '';
        visitorEndDate = '';
        visitorCurrentPage = 1;
        loadVisitorStats();
        loadVisitorLogs();
    });

    // Pagination event handlers
    $(document).on('click', '#prevVisitorsPage', function(e) {
        e.preventDefault();
        if (visitorCurrentPage > 1) {
            visitorCurrentPage--;
            loadVisitorLogs(visitorCurrentPage);
        }
    });

    $(document).on('click', '#nextVisitorsPage', function(e) {
        e.preventDefault();
        if (visitorCurrentPage < visitorTotalPages) {
            visitorCurrentPage++;
            loadVisitorLogs(visitorCurrentPage);
        }
    });

    $(document).on('click', '.visitor-page-number', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        visitorCurrentPage = page;
        loadVisitorLogs(page);
    });
});