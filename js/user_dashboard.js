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
                    $('#addUserModal').modal('hide');
                } else {
                    $('#userErrorMessage').text(response.message).show();
                    setTimeout(() => $('#userErrorMessage').hide(), 3000);
                }
            },
            error: function(xhr) {
                let errorMsg = 'An error occurred while adding user.';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) errorMsg = response.message;
                } catch (e) {}
                $('#userErrorMessage').text(errorMsg).show();
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
            error: function(xhr) {
                let errorMsg = 'An error occurred while updating user.';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) errorMsg = response.message;
                } catch (e) {}
                $('#warningMessage').text(errorMsg);
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
                if (response.success) {
                    $('#usersTableBody').empty();
                    
                    if (response.users && response.users.length > 0) {
                        response.users.forEach(function(user) {
                            $('#usersTableBody').append(`
                                <tr>
                                    <td>${user.username}</td>
                                    <td>${user.fullName || 'N/A'}</td>
                                    <td>${user.position || 'N/A'}</td>
                                    <td>${user.branch || 'N/A'}</td>
                                    <td>
                                        <button class="btn btn-sm btn-primary text-white edit-user" data-id="${user.id}">Edit</button>
                                        <button class="btn btn-sm btn-danger delete-user" data-id="${user.id}">Delete</button>
                                    </td>
                                </tr>
                            `);    
                        });
                        
                        updateUserPagination(response.current_page, response.total_pages);
                    } else {
                        $('#usersTableBody').append('<tr><td colspan="5" class="text-center">No users found</td></tr>');
                    }
                } else {
                    $('#usersTableBody').append('<tr><td colspan="5" class="text-center">Error: ' + (response.message || 'Failed to load users') + '</td></tr>');
                }
            },
            error: function(xhr) {
                $('#usersTableBody').html('<tr><td colspan="5" class="text-center">Error loading users. Please try again.</td></tr>');
                console.error('Error loading users:', xhr.responseText);
            }
        });
    }

    // Update User Pagination
    function updateUserPagination(currentPage, totalPages) {
        $('#usersPaginationControls').empty();
        
        if (totalPages <= 1) return;
        
        // Previous button
        let prevDisabled = currentPage <= 1 ? 'disabled' : '';
        $('#usersPaginationControls').append(`
            <li class="page-item ${prevDisabled}">
                <a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a>
            </li>
        `);
        
        // Show limited page numbers
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
                    $('#editUserBranch').val(response.user.branch || '');
                    $('#editUserModal').modal('show');
                } else {
                    $('#warningMessage').text(response.message || 'Failed to load user data');
                    $('#warningModal').modal('show');
                }
            },
            error: function(xhr) {
                $('#warningMessage').text('Error loading user data. Please try again.');
                $('#warningModal').modal('show');
                console.error('Error loading user:', xhr.responseText);
            }
        });
    });

    // Delete User Click Event
    $(document).on('click', '.delete-user', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        let userId = $(this).data('id');
        let username = $(this).closest('tr').find('td:first').text();
        
        // Store the user ID in the confirmation modal
        $('#confirmationModal').data('userId', userId);
        
        // Update the confirmation message
        $('#confirmationModal .modal-body').html(`
            <p>Are you sure you want to delete user <strong>${username}</strong>?</p>
            <p class="text-danger"><strong>This action cannot be undone.</strong></p>
        `);
        
        // Show the confirmation modal
        $('#confirmationModal').modal('show');
    });

    // Confirm Delete Button Handler
    $('#confirmDeleteBtn').off('click').on('click', function() {
        let userId = $('#confirmationModal').data('userId');
        
        if (!userId) {
            $('#warningMessage').text('No user selected for deletion');
            $('#warningModal').modal('show');
            $('#confirmationModal').modal('hide');
            return;
        }
        
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
                    $('#warningMessage').text(response.message || 'Failed to delete user');
                    $('#warningModal').modal('show');
                }
                $('#confirmationModal').modal('hide');
            },
            error: function(xhr) {
                let errorMsg = 'Error deleting user. Please try again.';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) errorMsg = response.message;
                } catch (e) {}
                
                $('#warningMessage').text(errorMsg);
                $('#warningModal').modal('show');
                $('#confirmationModal').modal('hide');
                console.error('Error deleting user:', xhr.responseText);
            }
        });
    });
});