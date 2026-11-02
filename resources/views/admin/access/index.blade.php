@extends('layouts.admin')

@php
    $title = 'Access Control & Role Permissions';
    $subTitle = 'Manage active accounts and assigned module permissions.';
@endphp

@section('content')

    <link rel="stylesheet" href="{{ asset('assets/css/access-control.css') }}">

    <div class="ac-tabs">
        @if($canViewUsers)
            <button class="ac-tab-btn active" onclick="switchTab('users')" id="btn-tab-users"><iconify-icon icon="ph:users"></iconify-icon> Users Management</button>
        @endif
        @if($canViewRoles)
            <button class="ac-tab-btn {{ $canViewUsers ? '' : 'active' }}" onclick="switchTab('roles')" id="btn-tab-roles"><iconify-icon icon="ph:shield-check"></iconify-icon> Roles & Permissions</button>
        @endif
    </div>

    <!-- USERS TAB -->
    @if($canViewUsers)
    <div id="tab-users" class="ac-card">
        <div class="ac-card-header">
            <h3 class="ac-card-title">User Directory</h3>
            @if($canManageUsers)<button class="btn-teal" onclick="openUserModal()">
                <iconify-icon icon="ph:plus"></iconify-icon> Add New User
            </button>@endif
        </div>

        <div class="table-responsive">
            <table class="table ac-table" id="usersTable">
                <thead>
                    <tr>
                        <th>User Name</th>
                        <th>Email Address</th>
                        <th>Role Assigned</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        <tr>
                            <td><strong style="color:#0f172a;">{{ $user->name }}</strong></td>
                            <td>{{ $user->email }}</td>
                            <td><span class="badge-role">{{ $user->roles->first()->name ?? 'No Role' }}</span></td>
                            <td><span
                                    class="badge-status {{ $user->is_active ? '' : 'inactive' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span>
                            </td>
                            <td>
                                @if($canManageUsers)<button class="action-btn"
                                    type="button"
                                    data-access-action="edit-user"
                                    data-user-id="{{ $user->id }}"
                                    data-user-name="{{ $user->name }}"
                                    data-user-email="{{ $user->email }}"
                                    data-user-role="{{ $user->roles->first()->name ?? '' }}"
                                    data-user-status="{{ $user->is_active ? 1 : 0 }}">
                                    <iconify-icon icon="ph:dots-three-vertical-bold"></iconify-icon>
                                </button>@endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $users->links() }}
    </div>
    @endif

    <!-- ROLES TAB -->
    @if($canViewRoles)
    <div id="tab-roles" class="ac-card" style="{{ $canViewUsers ? 'display:none;' : '' }}">
        <div class="ac-card-header">
            <h3 class="ac-card-title">Role Permissions Matrix</h3>
            @if($canManageRoles)<button class="btn-teal" onclick="openRoleModal()">
                <iconify-icon icon="ph:plus"></iconify-icon> Add New Role
            </button>@endif
        </div>

        <div class="table-responsive">
            <table class="table ac-table" id="rolesTable">
                <thead>
                    <tr>
                        <th>Role Title</th>
                        <th>Permissions Snapshot</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($roleRows as $role)
                        <tr>
                            <td><strong style="color:#0f172a;">{{ $role->name }}</strong></td>
                            <td>
                                @if($role->name === 'Super Admin')
                                    <span class="badge-perm-rw">Full System Access</span>
                                @else
                                    @php
                                        $perms = $role->permissions->pluck('name')->toArray();
                                        $modulesWithAccess = [];
                                        foreach ($modules as $m) {
                                            if (in_array("manage $m", $perms)) {
                                                $modulesWithAccess[$m] = 'Read / Write';
                                            } elseif (in_array("view $m", $perms)) {
                                                $modulesWithAccess[$m] = 'Read Only';
                                            }
                                        }
                                    @endphp

                                    @foreach($modulesWithAccess as $mod => $lvl)
                                        <span
                                            class="{{ $lvl == 'Read / Write' ? 'badge-perm-rw' : 'badge-perm-ro' }}">{{ ucfirst($mod) }}
                                            ({{ $lvl == 'Read / Write' ? 'R/W' : 'RO' }})</span>
                                    @endforeach

                                    @if(empty($modulesWithAccess))
                                        <span style="color:#94a3b8; font-size:0.85rem;">No permissions</span>
                                    @endif
                                @endif
                            </td>
                            <td>
                                @if($canManageRoles)<button class="action-btn"
                                    type="button"
                                    data-access-action="edit-role"
                                    data-role-id="{{ $role->id }}"
                                    data-role-name="{{ $role->name }}"
                                    data-role-permissions="{{ json_encode($modulesWithAccess ?? []) }}">
                                    <iconify-icon icon="ph:dots-three-vertical-bold"></iconify-icon>
                                </button>@endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $roleRows->links() }}
    </div>
    @endif

    <!-- USER MODAL -->
    @if($canManageUsers)
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content ac-modal-content">
                <div class="ac-modal-header d-flex justify-content-between align-items-center">
                    <h5 class="ac-modal-title m-0"><iconify-icon icon="ph:user-plus"></iconify-icon> <span
                            id="userModalTitle">Add New System User</span></h5>
                    <button type="button" class="ac-modal-close" data-bs-dismiss="modal"><iconify-icon
                            icon="ph:x"></iconify-icon></button>
                </div>
                <div class="modal-body p-4">
                    <form id="userForm">
                        @csrf
                        <input type="hidden" id="userId" name="id">
                        <div class="mb-3">
                            <label class="ac-form-label">User Full Name *</label>
                            <input type="text" class="form-control radius-8" id="userName" name="name" required
                                placeholder="e.g. Sushant Yadav">
                        </div>
                        <div class="mb-3">
                            <label class="ac-form-label">Email Address *</label>
                            <input type="email" class="form-control radius-8" id="userEmail" name="email" required
                                placeholder="user@example.com">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="ac-form-label">Role Assigned *</label>
                                <select class="form-select radius-8" id="userRole" name="role" required>
                                    <option value="">Select a role...</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->name }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="ac-form-label">Account Status</label>
                                <select class="form-select radius-8" id="userStatus" name="status">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="ac-form-label">Password <span id="pwHint"
                                    style="font-weight:400;color:#64748B;font-size:0.8rem;">(Required for new
                                    user)</span></label>
                            <input type="password" class="form-control radius-8" id="userPassword" name="password"
                                placeholder="Min 8 characters">
                        </div>

                        <div class="d-flex justify-content-end gap-3">
                            <button type="button" class="btn-light-ac"
                                data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn-teal" id="userSubmitBtn">Create User Account</button>
                            <button type="button" class="btn-danger-ac d-none" id="userDeleteBtn"
                                onclick="deleteUser()">Delete</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- ROLE MODAL -->
    @if($canManageRoles)
    <div class="modal fade" id="roleModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content ac-modal-content">
                <div class="ac-modal-header d-flex justify-content-between align-items-center">
                    <h5 class="ac-modal-title m-0"><iconify-icon icon="ph:shield-plus"></iconify-icon> <span
                            id="roleModalTitle">Add New Access Role & Permissions</span></h5>
                    <button type="button" class="ac-modal-close" data-bs-dismiss="modal"><iconify-icon
                            icon="ph:x"></iconify-icon></button>
                </div>
                <div class="modal-body p-4">
                    <form id="roleForm">
                        @csrf
                        <input type="hidden" id="roleId" name="id">
                        <div class="mb-4">
                            <label class="ac-form-label">Role Title *</label>
                            <input type="text" class="form-control radius-8" id="roleName" name="name" required
                                placeholder="e.g. Underwriting Specialist">
                        </div>

                        <label class="ac-form-label mb-2">Module Permission Levels (All Sidebar Menus)</label>
                        <table class="perm-matrix">
                            <thead>
                                <tr>
                                    <th>SIDEBAR NAVIGATION MENU</th>
                                    <th>VIEW (READ ONLY)</th>
                                    <th>MANAGE (READ / WRITE)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($modules as $index => $module)
                                    <tr>
                                        <td>
                                            <iconify-icon icon="ph:folder-notch"
                                                style="color:#0ea5e9; font-size:1.1rem;"></iconify-icon>
                                            {{ $index + 1 }}. {{ ucwords($module) }}
                                        </td>
                                        <td><input type="checkbox" name="permissions[{{ $module }}][view]" value="1"
                                                class="perm-checkbox" id="perm_{{ $index }}_view"></td>
                                        <td><input type="checkbox" name="permissions[{{ $module }}][manage]" value="1"
                                                class="perm-checkbox" id="perm_{{ $index }}_manage"></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="d-flex justify-content-end gap-3 mt-4">
                            <button type="button" class="btn-light-ac"
                                data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn-teal" id="roleSubmitBtn">Save Role Permissions</button>
                            <button type="button" class="btn-danger-ac d-none" id="roleDeleteBtn"
                                onclick="deleteRole()">Delete</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    <script>
        window.AC_ROUTES = {
            usersUrl: "{{ url('admin/access-control/users') }}",
            usersStoreUrl: "{{ route('admin.access-control.users.store') }}",
            rolesUrl: "{{ url('admin/access-control/roles') }}",
            rolesStoreUrl: "{{ route('admin.access-control.roles.store') }}"
        };
    </script>
    <script src="{{ asset('assets/js/access-control.js') }}?v={{ filemtime(public_path('assets/js/access-control.js')) }}"></script>

@endsection
