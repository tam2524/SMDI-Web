$(document).ready(function() {
    let selectedRecordIds = [];
    let RecordIdToDelete = null;
    let currentPage = 1;
    let totalPages = 1;

    // Convert input text to uppercase
    $('#addRecordForm input[type="text"], #editRecordForm input[type="text"]').on('keyup input', function() {
        $(this).val($(this).val().toUpperCase());
    });

    // Load records with pagination
    function loadRecords(query = '', page = 1) {
        $.ajax({
            url: '../api/fetch_Records.php',
            method: 'GET',
            data: { query: query, page: page },
            dataType: 'json',
            success: function(response) {
                $('#RecordTableBody').html(response.html);
                currentPage = response.pagination.currentPage;
                totalPages = response.pagination.totalPages;
                updatePaginationControls();
                updateSelectedRecords(); // Update checkboxes after loading
            },
            error: function(xhr, status, error) {
                console.error("Error loading records:", error);
                // Fallback for non-JSON response
                if (xhr.responseText) {
                    $('#RecordTableBody').html(xhr.responseText);
                }
            }
        });
    }

    // Update pagination controls
    function updatePaginationControls() {
        let paginationHtml = '';
        
        // Previous button
        paginationHtml += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" id="prevPage">Previous</a>
        </li>`;
        
        // Page numbers (show up to 5 pages around current page)
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, currentPage + 2);
        
        if (startPage > 1) {
            paginationHtml += `<li class="page-item"><a class="page-link page-number" href="#" data-page="1">1</a></li>`;
            if (startPage > 2) {
                paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            paginationHtml += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link page-number" href="#" data-page="${i}">${i}</a>
            </li>`;
        }
        
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
            paginationHtml += `<li class="page-item"><a class="page-link page-number" href="#" data-page="${totalPages}">${totalPages}</a></li>`;
        }
        
        // Next button
        paginationHtml += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" id="nextPage">Next</a>
        </li>`;
        
        $('#paginationControls').html(paginationHtml);
    }

    // Initial load of records
    loadRecords();
    
    // Pagination event handlers
    $(document).on('click', '#prevPage', function(e) {
        e.preventDefault();
        if (currentPage > 1) {
            loadRecords($('#searchInput').val(), currentPage - 1);
        }
    });

    $(document).on('click', '#nextPage', function(e) {
        e.preventDefault();
        if (currentPage < totalPages) {
            loadRecords($('#searchInput').val(), currentPage + 1);
        }
    });

    $(document).on('click', '.page-number', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        loadRecords($('#searchInput').val(), page);
    });

    // Search input event
    $('#searchInput').on('input', function() {
        currentPage = 1; // Reset to first page when searching
        loadRecords($(this).val());
    });

    // Update selected records array
    function updateSelectedRecords() {
        selectedRecordIds = [];
        $('#RecordTableBody input[name="recordCheckbox"]:checked').each(function() {
            selectedRecordIds.push($(this).closest('tr').data('id'));
        });
    }

    // Select all checkbox
    $('#selectAll').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('#RecordTableBody input[name="recordCheckbox"]').prop('checked', isChecked);
        updateSelectedRecords();
    });
// Delete single record button click
$('#RecordTableBody').on('click', '.delete-button', function() {
    RecordIdToDelete = $(this).closest('tr').data('id');
    selectedRecordIds = [RecordIdToDelete]; // Set the single record to delete
    // Uncheck all checkboxes and check the current one
    $('#RecordTableBody input[name="recordCheckbox"]').prop('checked', false);
    $(this).closest('tr').find('input[name="recordCheckbox"]').prop('checked', true);
    $('#confirmationModal').modal('show');
});

// Delete selected records button
$('#deleteSelectedButton').on('click', function() {
    updateSelectedRecords(); // Make sure we have the latest selection
    if (selectedRecordIds.length > 0) {
        RecordIdToDelete = null; // Clear any single selection
        $('#confirmationModal').modal('show');
    } else {
        showWarningModal('No records selected for deletion.');
    }
});

// Unified delete confirmation handler
$('#confirmDeleteBtn').on('click', function() {
    const idsToDelete = selectedRecordIds.length > 0 ? selectedRecordIds : 
                       (RecordIdToDelete ? [RecordIdToDelete] : []);
    
    if (idsToDelete.length === 0) {
        showWarningModal('No records selected for deletion.');
        return;
    }

    $.ajax({
        url: '../api/delete_Record.php',
        method: 'POST',
        data: { ids: idsToDelete },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                loadRecords($('#searchInput').val(), currentPage);
                showSuccessModal(response.message);
            } else {
                showErrorModal(response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error("Error deleting records:", error);
            showErrorModal('Failed to delete records. Please try again.');
        },
        complete: function() {
            $('#confirmationModal').modal('hide');
            selectedRecordIds = [];
            RecordIdToDelete = null;
            $('#selectAll').prop('checked', false);
        }
    });
});

    // Add record form submission
    $('#addRecordForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '../api/add_Record.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    loadRecords($('#searchInput').val(), currentPage);
                    $('#addRecordModal').modal('hide');
                    showSuccessModal(response.message);
                    $('#addRecordForm')[0].reset();
                    $('#addRecordForm input[type="text"]').val('');
                } else {
                    showErrorModal(response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error("Error adding record:", error);
            }
        });
    });

    // Edit record button click
    $('#RecordTableBody').on('click', '.edit-button', function() {
        let recordId = $(this).closest('tr').data('id');

        $.ajax({
            url: '../api/get_Record.php',
            method: 'GET',
            data: { id: recordId },
            success: function(response) {
                let record = JSON.parse(response);

                $('#editRecordId').val(record.record_id);
                $('#editFamilyName').val(record.family_name);
                $('#editFirstName').val(record.first_name);
                $('#editMiddleName').val(record.middle_name);
                $('#editPlateNumber').val(record.plate_number);
                $('#editMvFile').val(record.mv_file);
                $('#editBranch').val(record.branch);
                $('#editBatch').val(record.batch);
                $('#editRemarks').val(record.remarks);
                $('#editDateReg').val(record.date_reg);

                $('#editRecordModal').modal('show');
            },
            error: function() {
                alert('Error fetching the record');
            }
        });
    });

    // Edit record form submission
    $('#editRecordForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '../api/edit_Record.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    loadRecords($('#searchInput').val(), currentPage);
                    $('#editRecordModal').modal('hide');
                    showSuccessModal(response.message);
                } else {
                    showErrorModal(response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error("Error editing Record:", error);
            }
        });
    });


    // Sorting functionality
    $('.dropdown-item').on('click', function(e) {
        e.preventDefault();
        const sortOption = $(this).data('sort');
        let sortColumn;

        switch (sortOption) {
            case 'familyName':
                sortColumn = 'family_name';
                break;
            case 'batch':
                sortColumn = 'batch';
                break;
            case 'branch':
                sortColumn = 'branch';
                break;
            default:
                return;
        }

        currentPage = 1; // Reset to first page when sorting
        loadRecords($('#searchInput').val(), currentPage, sortColumn);
    });

    // Print options modal
    $('#printButton').on('click', function() {
        $('#printOptionsModal').modal('show');
    });

    // Show/hide range inputs based on sort selection
    $('#sortBy').on('change', function() {
        const selectedValue = $(this).val();
        $('#batchRange').hide();
        $('#familyNameRange').hide();

        if (selectedValue === 'customerBatchRange') {
            $('#batchRange').show();
        } else if (selectedValue === 'familyName') {
            $('#familyNameRange').show();
        }
    });

    // Handle print confirmation
    $('#confirmPrint').on('click', function() {
        const documentType = $('#documentType').val();
        const sortBy = $('#sortBy').val();
        const fromBatch = $('#fromBatch').val();
        const toBatch = $('#toBatch').val();
        const fromLetter = $('#fromLetter').val();
        const toLetter = $('#toLetter').val();
        const outputFormat = $('#outputFormat').val();

        if (documentType && sortBy) {
            if (sortBy === 'customerBatchRange' && (fromBatch === '' || toBatch === '')) {
                alert('Please enter both From and To batch numbers.');
                return;
            }

            if (sortBy === 'familyName' && (fromLetter === '' || toLetter === '')) {
                alert('Please enter both From and To letters.');
                return;
            }

            const params = new URLSearchParams({
                documentType: documentType,
                sortBy: sortBy,
                fromBatch: fromBatch,
                toBatch: toBatch,
                fromLetter: fromLetter,
                toLetter: toLetter,
                outputFormat: outputFormat
            });

            window.location.href = `../api/generate_pdf.php?${params.toString()}`;
            $('#printOptionsModal').modal('hide');
        } else {
            alert('Please select all options.');
        }
    });

    // Modal functions
    function showSuccessModal(message) {
        $('#successMessage').text(message);
        $('#successModal').modal('show');
        setTimeout(() => {
            $('#successModal').modal('hide');
        }, 2000);
    }

    function showErrorModal(message) {
        $('#errorMessage').text(message);
        $('#errorMessage').show();
        setTimeout(() => {
            $('#errorMessage').hide();
        }, 3000);
    }

    function showWarningModal(message) {
        $('#warningMessage').text(message);
        $('#warningModal').modal('show');
        setTimeout(() => {
            $('#warningModal').modal('hide');
        }, 2000);
    }
});

//USER
$(document).ready(function() {
    // Load users when the user management tab is shown
    $('#users-tab').on('click', function() {
        loadUsers();
    });

    // Add User Form Submission
    $('#addUserForm').submit(function(e) {
        e.preventDefault();
        
        if ($('#newPassword').val() !== $('#confirmPassword').val()) {
            $('#userErrorMessage').text('Passwords do not match').show();
            return;
        }
        
        $.ajax({
            url: '../api/user_management.php?action=add_user',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    $('#userSuccessMessage').text(response.message).show();
                    $('#addUserForm')[0].reset();
                    loadUsers();
                    setTimeout(() => $('#userSuccessMessage').hide(), 3000);
                } else {
                    $('#userErrorMessage').text(response.message).show();
                    setTimeout(() => $('#userErrorMessage').hide(), 3000);
                }
            },
            error: function() {
                $('#userErrorMessage').text('An error occurred. Please try again.').show();
                setTimeout(() => $('#userErrorMessage').hide(), 3000);
            }
        });
    });

    // Edit User Form Submission
    $('#editUserForm').submit(function(e) {
        e.preventDefault();
        
        $.ajax({
            url: '../api/user_management.php?action=edit_user',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    $('#successMessage').text(response.message);
                    $('#successModal').modal('show');
                    $('#editUserModal').modal('hide');
                    loadUsers();
                } else {
                    $('#warningMessage').text(response.message);
                    $('#warningModal').modal('show');
                }
            },
            error: function() {
                $('#warningMessage').text('An error occurred. Please try again.');
                $('#warningModal').modal('show');
            }
        });
    });

    // Load Users Function
    function loadUsers(page = 1, search = '') {
        $.ajax({
            url: '../api/user_management.php?action=get_users',
            type: 'GET',
            data: { page: page, search: search },
            success: function(response) {
                $('#usersTableBody').empty();
                
                if (response.users && response.users.length > 0) {
                    response.users.forEach(function(user) {
                        let statusBadge = user.status === 'active' ? 
                            '<span class="badge bg-success">Active</span>' : 
                            '<span class="badge bg-danger">Inactive</span>';
                        
                        $('#usersTableBody').append(`
                            <tr>
                                <td>${user.username}</td>
                                <td>${user.fullName || 'N/A'}</td>
                                <td>${user.position || 'N/A'}</td>
                                <td>${user.branch || 'N/A'}</td>
                                <td>
                                    <button class="btn btn-sm text-white btn-primary edit-user" data-id="${user.id}">Edit</button>
                                    <button class="btn btn-sm text-white btn-primary delete-user" data-id="${user.id}">Delete</button>
                                </td>
                            </tr>
                        `);    
                    });
                    
                    // Update pagination controls
                    updateUserPagination(response.current_page, response.total_pages);
                } else {
                    $('#usersTableBody').append('<tr><td colspan="8" class="text-center">No users found</td></tr>');
                }
            },
            error: function() {
                $('#usersTableBody').append('<tr><td colspan="8" class="text-center">Error loading users</td></tr>');
            }
        });
    }

    // Update User Pagination
    function updateUserPagination(currentPage, totalPages) {
        $('#usersPaginationControls').empty();
        
        // Previous button
        let prevDisabled = currentPage <= 1 ? 'disabled' : '';
        $('#usersPaginationControls').append(`
            <li class="page-item ${prevDisabled}">
                <a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a>
            </li>
        `);
        
        // Show limited page numbers (1-5 or current page ±2)
        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(totalPages, currentPage + 2);
        
        if (startPage > 1) {
            $('#usersPaginationControls').append(`
                <li class="page-item">
                    <a class="page-link" href="#" data-page="1">1</a>
                </li>
                ${startPage > 2 ? '<li class="page-item disabled"><span class="page-link">...</span></li>' : ''}
            `);
        }
        
        for (let i = startPage; i <= endPage; i++) {
            let active = i === currentPage ? 'active' : '';
            $('#usersPaginationControls').append(`
                <li class="page-item ${active}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `);
        }
        
        if (endPage < totalPages) {
            $('#usersPaginationControls').append(`
                ${endPage < totalPages - 1 ? '<li class="page-item disabled"><span class="page-link">...</span></li>' : ''}
                <li class="page-item">
                    <a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a>
                </li>
            `);
        }
        
        // Next button
        let nextDisabled = currentPage >= totalPages ? 'disabled' : '';
        $('#usersPaginationControls').append(`
            <li class="page-item ${nextDisabled}">
                <a class="page-link" href="#" data-page="${currentPage + 1}">Next</a>
            </li>
        `);
    }

    // Pagination Click Event
    $(document).on('click', '#usersPaginationControls .page-link', function(e) {
        e.preventDefault();
        if ($(this).parent().hasClass('disabled')) return;
        let page = $(this).data('page');
        let search = $('#searchUserInput').val();
        loadUsers(page, search);
    });

    // Search Users with debounce
    let searchTimeout;
    $('#searchUserInput').on('keyup', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            let search = $(this).val();
            loadUsers(1, search);
        }, 500);
    });

    // Edit User Click Event
    $(document).on('click', '.edit-user', function() {
        let userId = $(this).data('id');
        
        $.ajax({
            url: '../api/user_management.php?action=get_user',
            type: 'GET',
            data: { id: userId },
            success: function(response) {
                if (response.success) {
                    $('#editUserId').val(response.user.id);
                    $('#editUsername').val(response.user.username);
                    $('#editFullName').val(response.user.fullName || '');
                    $('#editPosition').val(response.user.position || '');
                    $('#editBranch').val(response.user.branch || '');
                    $('#editRole').val(response.user.role || 'staff');
                    $('#editStatus').val(response.user.status || 'active');
                    $('#editUserModal').modal('show');
                } else {
                    $('#warningMessage').text(response.message);
                    $('#warningModal').modal('show');
                }
            },
            error: function() {
                $('#warningMessage').text('An error occurred. Please try again.');
                $('#warningModal').modal('show');
            }
        });
    });

    // Delete User Click Event
    $(document).on('click', '.delete-user', function() {
        let userId = $(this).data('id');
        
        // Set confirmation modal content
        $('#userConfirmationModal .modal-body').html(`
            <p>Are you sure you want to delete this user?</p>
            <p class="text-danger"><strong>This action cannot be undone.</strong></p>
        `);
        
        $('#userConfirmationModal').modal('show');
        $('#confirmUserActionBtn').off('click').on('click', function() {
            $.ajax({
                url: '../api/user_management.php?action=delete_user',
                type: 'POST',
                data: { id: userId },
                success: function(response) {
                    if (response.success) {
                        $('#successMessage').text(response.message);
                        $('#successModal').modal('show');
                        loadUsers();
                    } else {
                        $('#warningMessage').text(response.message);
                        $('#warningModal').modal('show');
                    }
                    $('#userConfirmationModal').modal('hide');
                },
                error: function() {
                    $('#warningMessage').text('An error occurred. Please try again.');
                    $('#warningModal').modal('show');
                    $('#userConfirmationModal').modal('hide');
                }
            });
        });
    });
});

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