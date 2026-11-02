<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = collect(['view dashboard', 'manage dashboard'])
            ->map(fn (string $name) => Permission::findOrCreate($name, 'admin'));

        Role::query()
            ->where('name', 'Super Admin')
            ->where('guard_name', 'admin')
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo($permissions));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Keep dashboard access during code rollback so administrators are not locked out.
    }
};
