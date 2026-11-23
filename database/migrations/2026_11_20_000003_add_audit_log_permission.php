<?php

/**
 * DATABASE_RULES note:
 * Adds one Spatie permission row for the admin guard and grants it to the Super Admin
 * role. Uses findOrCreate to be idempotent and safe against repeated runs (e.g. in CI).
 * Rollback removes the permission row; losing it only affects the UI menu — it does not
 * delete any audit log data.
 */
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Exceptions\PermissionAlreadyExists;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $perm = Permission::findOrCreate('view audit log', 'admin');

        // findOrCreate is idempotent; givePermissionTo only if the role actually exists.
        Role::query()
            ->where('name', 'Super Admin')
            ->where('guard_name', 'admin')
            ->get()
            ->each(function (Role $role) use ($perm): void {
                try {
                    $role->givePermissionTo($perm);
                } catch (PermissionAlreadyExists) {
                    // Permission already assigned — no-op.
                }
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', 'view audit log')->where('guard_name', 'admin')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
