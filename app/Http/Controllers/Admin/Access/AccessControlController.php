<?php

namespace App\Http\Controllers\Admin\Access;

use App\Http\Controllers\Controller;
use App\Services\Access\AccessControlService;
use App\Http\Requests\Admin\Access\StoreAdminUserRequest;
use App\Http\Requests\Admin\Access\StoreRoleRequest;
use App\Http\Requests\Admin\Access\UpdateAdminUserRequest;
use App\Http\Requests\Admin\Access\UpdateRoleRequest;
use Spatie\Permission\Models\Role;
use App\Models\User;

class AccessControlController extends Controller
{
    public function __construct(private readonly AccessControlService $accessControl) {}

    public function index()
    {
        $actor = auth('admin')->user();
        $canViewUsers = $actor->can('view users') || $actor->can('manage users');
        $canManageUsers = $actor->can('manage users');
        $canViewRoles = $actor->can('view roles') || $actor->can('manage roles');
        $canManageRoles = $actor->can('manage roles');

        // Role options are also needed when someone can manage users but cannot
        // browse the role matrix. Keep this small reference list unpaginated.
        $roles = ($canViewRoles || $canManageUsers)
            ? Role::query()->where('guard_name', 'admin')->orderBy('name')->get(['id', 'name'])
            : collect();
        $roleRows = $canViewRoles
            ? Role::query()->where('guard_name', 'admin')->with('permissions')->orderBy('name')->paginate(25, ['*'], 'roles_page')
            : collect();
        $users = $canViewUsers
            ? User::query()
                ->where('role', 'admin')
                ->whereHas('roles', fn ($query) => $query->where('guard_name', 'admin'))
                ->with(['roles' => fn ($query) => $query->where('guard_name', 'admin')->orderBy('name')])
                ->orderBy('id')
                ->paginate(25, ['id', 'name', 'email', 'is_active'], 'users_page')
            : collect();
        
        $modules = [
            'dashboard',
            'analytics',
            'diet chart',
            'products',
            'orders',
            'customers',
            'promotions',
            'loyalty',
            'moderation',
            'administration',
            'content',
            'assessment',
            'users',
            'roles'
        ];

        return view('admin.access.index', compact('roles', 'roleRows', 'users', 'modules', 'canViewUsers', 'canManageUsers', 'canViewRoles', 'canManageRoles'));
    }

    public function storeRole(StoreRoleRequest $request)
    {
        $this->accessControl->createRole($request->name, $request->permissions);

        return response()->json(['success' => true, 'message' => 'Role created successfully.']);
    }

    public function updateRole(UpdateRoleRequest $request, Role $role)
    {
        abort_unless($role->guard_name === 'admin', 404);

        if ($role->name === 'Super Admin') {
            return response()->json(['success' => false, 'message' => 'Cannot edit the Super Admin role.'], 403);
        }

        $this->accessControl->updateRole($role, $request->name, $request->permissions);

        return response()->json(['success' => true, 'message' => 'Role updated successfully.']);
    }

    public function destroyRole(Role $role)
    {
        abort_unless($role->guard_name === 'admin', 404);

        if ($role->name === 'Super Admin') {
            return response()->json(['success' => false, 'message' => 'Cannot delete the Super Admin role.'], 403);
        }

        $role->delete();

        return response()->json(['success' => true, 'message' => 'Role deleted successfully.']);
    }

    // --- Users ---
    
    public function storeUser(StoreAdminUserRequest $request)
    {
        $this->accessControl->createAdmin($request->validated());

        return response()->json(['success' => true, 'message' => 'User created successfully.']);
    }

    public function updateUser(UpdateAdminUserRequest $request, User $admin)
    {
        abort_unless($this->isManagedAdmin($admin), 404);

        $this->accessControl->updateAdmin($admin, $request->validated());

        return response()->json(['success' => true, 'message' => 'User updated successfully.']);
    }

    public function destroyUser(User $admin)
    {
        abort_unless($this->isManagedAdmin($admin), 404);

        if ($admin->id === auth('admin')->id()) {
            return response()->json(['success' => false, 'message' => 'Cannot delete yourself.'], 403);
        }

        $admin->delete();

        return response()->json(['success' => true, 'message' => 'User deleted successfully.']);
    }

    private function isManagedAdmin(User $user): bool
    {
        return $user->role === 'admin' && $user->roles()->where('guard_name', 'admin')->exists();
    }
}
