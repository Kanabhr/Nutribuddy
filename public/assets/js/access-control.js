function switchTab(tab) {
        document.getElementById('tab-users')?.style && (document.getElementById('tab-users').style.display = 'none');
        document.getElementById('tab-roles')?.style && (document.getElementById('tab-roles').style.display = 'none');
        document.getElementById('btn-tab-users')?.classList.remove('active');
        document.getElementById('btn-tab-roles')?.classList.remove('active');
        
        const panel = document.getElementById('tab-' + tab);
        const button = document.getElementById('btn-tab-' + tab);
        if (panel && button) {
            panel.style.display = 'block';
            button.classList.add('active');
        }
    }
    
    // USERS LOGIC
    let userModalInstance = null;
    function getUserModal() {
        if (!userModalInstance) userModalInstance = new bootstrap.Modal(document.getElementById('userModal'));
        return userModalInstance;
    }
    
    function openUserModal() {
        document.getElementById('userForm').reset();
        document.getElementById('userId').value = '';
        document.getElementById('userModalTitle').innerText = 'Add New System User';
        document.getElementById('userSubmitBtn').innerText = 'Create User Account';
        document.getElementById('userDeleteBtn').classList.add('d-none');
        document.getElementById('pwHint').innerText = '(Required for new user)';
        document.getElementById('userPassword').required = true;
        getUserModal().show();
    }
    
    function editUser(id, name, email, role, status) {
        document.getElementById('userForm').reset();
        document.getElementById('userId').value = id;
        document.getElementById('userName').value = name;
        document.getElementById('userEmail').value = email;
        document.getElementById('userRole').value = role;
        document.getElementById('userStatus').value = status;
        
        document.getElementById('userModalTitle').innerText = 'Edit System User';
        document.getElementById('userSubmitBtn').innerText = 'Save Changes';
        document.getElementById('userDeleteBtn').classList.remove('d-none');
        document.getElementById('pwHint').innerText = '(Leave blank to keep current)';
        document.getElementById('userPassword').required = false;
        getUserModal().show();
    }
    
    document.getElementById('userForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const id = document.getElementById('userId').value;
        const url = id ? window.AC_ROUTES.usersUrl + "/" + id : window.AC_ROUTES.usersStoreUrl;
        const method = id ? 'PUT' : 'POST';
        
        const formData = new FormData(this);
        if (id) { formData.append('_method', 'PUT'); }
        
        fetch(url, {
            method: 'POST', // always POST with _method=PUT for Laravel
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Error occurred');
            }
        }).catch(err => {
            console.error(err);
            alert('A network error occurred. See console.');
        });
    });
    
    function deleteUser() {
        if(!confirm('Are you sure you want to delete this user?')) return;
        const id = document.getElementById('userId').value;
        fetch(window.AC_ROUTES.usersUrl + "/" + id, {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('input[name="_csrf"]').value || document.querySelector('meta[name="csrf-token"]')?.content
            }
        }).then(r => r.json()).then(data => {
            if(data.success) window.location.reload();
            else alert(data.message);
        });
    }
    
    // ROLES LOGIC
    let roleModalInstance = null;
    function getRoleModal() {
        if (!roleModalInstance) roleModalInstance = new bootstrap.Modal(document.getElementById('roleModal'));
        return roleModalInstance;
    }
    
    function openRoleModal() {
        document.getElementById('roleForm').reset();
        document.getElementById('roleId').value = '';
        document.getElementById('roleName').value = '';
        document.getElementById('roleName').readOnly = false;
        document.getElementById('roleModalTitle').innerText = 'Add New Access Role & Permissions';
        document.getElementById('roleSubmitBtn').innerText = 'Create Role';
        document.getElementById('roleDeleteBtn').classList.add('d-none');
        
        // Reset all checkboxes to unchecked
        document.querySelectorAll('.perm-checkbox').forEach(cb => cb.checked = false);
        
        getRoleModal().show();
    }
    
    function editRole(id, name, permissions) {
        document.getElementById('roleForm').reset();
        document.getElementById('roleId').value = id;
        document.getElementById('roleName').value = name;
        
        if (name === 'Super Admin') {
            document.getElementById('roleName').readOnly = true;
            document.getElementById('roleSubmitBtn').disabled = true;
            alert("Super Admin permissions cannot be modified.");
            return;
        } else {
            document.getElementById('roleName').readOnly = false;
            document.getElementById('roleSubmitBtn').disabled = false;
        }
        
        document.getElementById('roleModalTitle').innerText = 'Edit Access Role & Permissions';
        document.getElementById('roleSubmitBtn').innerText = 'Save Changes';
        document.getElementById('roleDeleteBtn').classList.remove('d-none');
        
        // Reset to unchecked
        document.querySelectorAll('.perm-checkbox').forEach(cb => cb.checked = false);
        
        // Set values based on current permissions
        for (const [module, level] of Object.entries(permissions)) {
            const viewCb = document.querySelector(`input[name="permissions[${module}][view]"]`);
            const manageCb = document.querySelector(`input[name="permissions[${module}][manage]"]`);
            
            if (level === 'Read Only' && viewCb) {
                viewCb.checked = true;
            } else if (level === 'Read / Write') {
                if (viewCb) viewCb.checked = true;
                if (manageCb) manageCb.checked = true;
            }
        }
        
        getRoleModal().show();
    }
    
    document.getElementById('roleForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const id = document.getElementById('roleId').value;
        const url = id ? window.AC_ROUTES.rolesUrl + "/" + id : window.AC_ROUTES.rolesStoreUrl;
        
        const formData = new FormData(this);
        if (id) { formData.append('_method', 'PUT'); }
        
        fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.location.hash = 'roles';
                window.location.reload();
            } else {
                alert(data.message || 'Error occurred');
            }
        }).catch(err => {
            console.error(err);
            alert('A network error occurred. See console.');
        });
    });
    
    function deleteRole() {
        if(!confirm('Are you sure you want to delete this role?')) return;
        const id = document.getElementById('roleId').value;
        fetch(window.AC_ROUTES.rolesUrl + "/" + id, {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            }
        }).then(r => r.json()).then(data => {
            if(data.success) {
                window.location.hash = 'roles';
                window.location.reload();
            }
            else alert(data.message);
        });
    }

    document.addEventListener('click', function (event) {
        const button = event.target.closest('[data-access-action]');
        if (!button) return;

        if (button.dataset.accessAction === 'edit-user') {
            editUser(
                Number(button.dataset.userId),
                button.dataset.userName,
                button.dataset.userEmail,
                button.dataset.userRole,
                Number(button.dataset.userStatus)
            );
        }

        if (button.dataset.accessAction === 'edit-role') {
            try {
                editRole(
                    Number(button.dataset.roleId),
                    button.dataset.roleName,
                    JSON.parse(button.dataset.rolePermissions || '{}')
                );
            } catch (error) {
                console.error('Unable to read role permissions for editing.', error);
                alert('This role could not be opened. Refresh the page and try again.');
            }
        }
    });
    
    // Auto switch to roles tab if hash is present
    if (window.location.hash === '#roles') {
        switchTab('roles');
    }
