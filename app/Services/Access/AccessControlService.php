<?php

namespace App\Services\Access;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/** Keeps administrator records, role assignments, and role permissions in sync. */
class AccessControlService
{
    public function __construct(private readonly RolePermissionSyncService $rolePermissions) {}

    public function createRole(string $name, mixed $permissions): Role
    {
        return DB::transaction(function () use ($name, $permissions): Role {
            $role = Role::create(['name' => $name, 'guard_name' => 'admin']);
            $this->rolePermissions->sync($role, $permissions);

            return $role;
        });
    }

    public function updateRole(Role $role, string $name, mixed $permissions): void
    {
        DB::transaction(function () use ($role, $name, $permissions): void {
            $role = Role::query()->whereKey($role->id)->lockForUpdate()->firstOrFail();
            abort_unless($role->guard_name === 'admin', 404);
            $role->update(['name' => $name]);
            $this->rolePermissions->sync($role, $permissions);
        });
    }

    /** @param array{name: string, email: string, password: string, role: string, status: string|int} $data */
    public function createAdmin(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $admin = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                // The User model hashes passwords through its cast.
                'password' => $data['password'],
                // AuthenticationController checks this legacy discriminator.
                'role' => 'admin',
                'is_active' => $data['status'],
            ]);
            $admin->assignRole($data['role']);

            return $admin;
        });
    }

    /** @param array{name: string, email: string, password?: string|null, role: string, status: string|int} $data */
    public function updateAdmin(User $admin, array $data): void
    {
        DB::transaction(function () use ($admin, $data): void {
            $admin = User::query()->whereKey($admin->id)->lockForUpdate()->firstOrFail();
            abort_unless($admin->role === 'admin' && $admin->roles()->where('guard_name', 'admin')->exists(), 404);
            $attributes = [
                'name' => $data['name'],
                'email' => $data['email'],
                // Keep the legacy admin login discriminator fixed on updates.
                'role' => 'admin',
                'is_active' => $data['status'],
            ];

            if (! empty($data['password'])) {
                $attributes['password'] = $data['password'];
            }

            $admin->update($attributes);
            $admin->syncRoles([$data['role']]);
        });
    }
}
