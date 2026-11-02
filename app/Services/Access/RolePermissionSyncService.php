<?php

namespace App\Services\Access;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSyncService
{
    private const MODULES = [
        'dashboard', 'analytics', 'diet chart', 'products', 'orders', 'customers',
        'promotions', 'loyalty', 'moderation', 'administration', 'content',
        'assessment', 'users', 'roles',
    ];

    public function sync(Role $role, mixed $permissionsArray): void
    {
        abort_unless($role->guard_name === 'admin', 404);

        $permissions = [];
        if (is_array($permissionsArray)) {
            foreach ($permissionsArray as $module => $levels) {
                if (! in_array($module, self::MODULES, true) || ! is_array($levels)) {
                    continue;
                }

                if (isset($levels['view']) && $levels['view'] == '1') {
                    $permissions[] = 'view ' . $module;
                }
                if (isset($levels['manage']) && $levels['manage'] == '1') {
                    $permissions[] = 'manage ' . $module;
                    if (! in_array('view ' . $module, $permissions, true)) {
                        $permissions[] = 'view ' . $module;
                    }
                }
            }
        }

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'admin']);
        }

        $role->syncPermissions($permissions);
    }
}
