let currentPage = 1;
let currentSearch = '';

document.addEventListener('DOMContentLoaded', function() {
    loadUsers();

    let searchTimer;
    document.getElementById('userSearchInput').addEventListener('input', function(e) {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            currentSearch = this.value;
            currentPage = 1;
            loadUsers();
        }, 350); 
    });

    document.getElementById('prevPageBtn').addEventListener('click', function() {
        if (currentPage > 1) {
            currentPage--;
            loadUsers();
        }
    });

    document.getElementById('nextPageBtn').addEventListener('click', function() {
        currentPage++;
        loadUsers();
    });

    document.getElementById('userForm').addEventListener('submit', handleUserSubmit);
});

function loadUsers() {
    fetch(`api_user_management.php?action=get_users&page=${currentPage}&search=${encodeURIComponent(currentSearch)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderUsersTable(data.users);
                
                document.getElementById('pageInfo').textContent = `Showing page ${data.current_page} of ${data.total_pages || 1} (${data.total} total users)`;
                document.getElementById('prevPageBtn').disabled = data.current_page <= 1;
                document.getElementById('nextPageBtn').disabled = data.current_page >= data.total_pages;
                
                if (currentPage > data.total_pages && data.total_pages > 0) {
                    currentPage = data.total_pages;
                    loadUsers();
                }
            } else {
                Swal.fire('Error', data.message || 'Failed to load users', 'error');
            }
        })
        .catch(err => console.error(err));
}

function renderUsersTable(users) {
    const tbody = document.getElementById('usersTableBody');
    tbody.innerHTML = '';
    
    if (users.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No users found</td></tr>';
        return;
    }

    users.forEach(user => {
        let badgeClass = 'bg-primary';
        if (user.position.includes('Admin')) badgeClass = 'bg-danger';
        if (user.position.includes('Sales') || user.position.includes('Retail')) badgeClass = 'bg-success';
        if (user.position.includes('Owner')) badgeClass = 'bg-dark';

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="ps-4 fw-bold align-middle">${user.username}</td>
            <td class="align-middle">${user.fullName || '<span class="text-muted small">N/A</span>'}</td>
            <td class="align-middle"><span class="badge ${badgeClass} bg-opacity-75">${user.position}</span></td>
            <td class="align-middle">${user.branch || 'HEADOFFICE'}</td>
            <td class="text-center pe-4 align-middle">
                <button class="btn btn-sm btn-outline-primary shadow-sm" onclick="editUser(${user.id})" title="Edit User">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger shadow-sm ms-1" onclick="deleteUser(${user.id})" title="Delete User">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function openAddUserModal() {
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('userModalTitle').innerHTML = '<i class="bi bi-person-plus me-2"></i>Add User';
    
    // Required fields for NEW users
    document.getElementById('formPassword').required = true;
    document.getElementById('formConfirmPassword').required = true;
    
    new bootstrap.Modal(document.getElementById('userModal')).show();
}

function editUser(id) {
    fetch(`api_user_management.php?action=get_user&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const u = data.user;
                document.getElementById('userId').value = u.id;
                document.getElementById('formUsername').value = u.username;
                document.getElementById('formFullName').value = u.fullName || '';
                document.getElementById('formPosition').value = u.position || 'Spareparts-Retail';
                document.getElementById('formBranch').value = u.branch || '';
                document.getElementById('formReportHeaderTitle').value = u.report_header_title || '';
                
                document.getElementById('formPassword').value = '';
                document.getElementById('formConfirmPassword').value = '';
                
                // NOT required for edit unless they type something
                document.getElementById('formPassword').required = false;
                document.getElementById('formConfirmPassword').required = false;

                document.getElementById('userModalTitle').innerHTML = '<i class="bi bi-pencil-square me-2"></i>Edit User';
                new bootstrap.Modal(document.getElementById('userModal')).show();
            } else {
                Swal.fire('Error', data.message || 'Could not load user data', 'error');
            }
        });
}

function handleUserSubmit(e) {
    e.preventDefault();
    
    const id = document.getElementById('userId').value;
    const action = id ? 'edit_user' : 'add_user';
    const pwd = document.getElementById('formPassword').value;
    const pwdConfirm = document.getElementById('formConfirmPassword').value;
    
    if (pwd !== pwdConfirm) {
        Swal.fire('Warning', 'Passwords do not match', 'warning');
        return;
    }
    
    // If we're editing, and password is empty, don't send it so we don't overwrite it to empty.
    const formData = new FormData(this);
    if (id && !pwd) {
        formData.delete('password');
        formData.delete('confirmPassword');
    }

    const payload = Object.fromEntries(formData.entries());

    fetch(`api_user_management.php?action=${action}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('userModal')).hide();
            Swal.fire('Success', data.message, 'success');
            loadUsers();
        } else {
            Swal.fire('Error', data.message || 'Operation failed', 'error');
        }
    });
}

function deleteUser(id) {
    Swal.fire({
        title: 'Delete User?',
        text: "This action cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('api_user_management.php?action=delete_user', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Deleted!', 'User has been deleted.', 'success');
                    loadUsers();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        }
    });
}
