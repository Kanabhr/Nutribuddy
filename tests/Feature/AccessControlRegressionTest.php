<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AccessControlRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function test_admin_user_role_changes_are_persisted_with_the_user(): void
    {
        $admin = $this->adminWithUserManagementPermission();
        Role::create(['name' => 'Content Reviewer', 'guard_name' => 'admin']);
        Role::create(['name' => 'Operations Manager', 'guard_name' => 'admin']);

        $this->actingAs($admin, 'admin')
            ->postJson(route('admin.access-control.users.store'), [
                'name' => 'Taylor Admin',
                'email' => 'taylor@example.test',
                'password' => 'secure-password',
                'role' => 'Content Reviewer',
                'status' => '1',
            ])
            ->assertOk()
            ->assertExactJson(['success' => true, 'message' => 'User created successfully.']);

        $created = User::where('email', 'taylor@example.test')->firstOrFail();
        $this->assertSame('admin', $created->role);
        $this->assertTrue($created->is_active);
        $this->assertTrue($created->hasRole('Content Reviewer', 'admin'));

        $this->putJson(route('admin.access-control.users.update', $created), [
            'name' => 'Taylor Operations',
            'email' => 'taylor@example.test',
            'role' => 'Operations Manager',
            'status' => '0',
        ])
            ->assertOk()
            ->assertExactJson(['success' => true, 'message' => 'User updated successfully.']);

        $created->refresh();
        $this->assertSame('Taylor Operations', $created->name);
        $this->assertFalse($created->is_active);
        $this->assertTrue($created->hasRole('Operations Manager', 'admin'));
        $this->assertFalse($created->hasRole('Content Reviewer', 'admin'));
    }

    public function test_admin_account_cannot_be_assigned_a_role_from_another_guard(): void
    {
        $admin = $this->adminWithUserManagementPermission();
        Role::create(['name' => 'Customer', 'guard_name' => 'web']);

        $this->actingAs($admin, 'admin')
            ->postJson(route('admin.access-control.users.store'), [
                'name' => 'Invalid Role User',
                'email' => 'invalid-role@example.test',
                'password' => 'secure-password',
                'role' => 'Customer',
                'status' => '1',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'invalid-role@example.test']);
    }

    public function test_user_manager_cannot_update_or_delete_a_customer_account(): void
    {
        $manager = $this->adminWithUserManagementPermission();
        $customer = User::factory()->create(['role' => 'user']);
        Role::create(['name' => 'Operations Manager', 'guard_name' => 'admin']);

        $this->actingAs($manager, 'admin')
            ->putJson(route('admin.access-control.users.update', $customer), [
                'name' => 'Changed Customer',
                'email' => $customer->email,
                'role' => 'Operations Manager',
                'status' => '1',
            ])
            ->assertNotFound();

        $this->deleteJson(route('admin.access-control.users.destroy', $customer))->assertNotFound();
        $this->assertDatabaseHas('users', ['id' => $customer->id, 'role' => 'user', 'name' => $customer->name]);
    }

    public function test_role_manager_cannot_update_or_delete_a_role_from_another_guard(): void
    {
        Permission::create(['name' => 'manage roles', 'guard_name' => 'admin']);
        $managerRole = Role::create(['name' => 'Role Manager', 'guard_name' => 'admin']);
        $managerRole->givePermissionTo('manage roles');
        $manager = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $manager->assignRole($managerRole);
        $webRole = Role::create(['name' => 'Customer Role', 'guard_name' => 'web']);

        $this->actingAs($manager, 'admin')
            ->putJson(route('admin.access-control.roles.update', $webRole), [
                'name' => 'Renamed Customer Role',
                'permissions' => [],
            ])
            ->assertNotFound();

        $this->deleteJson(route('admin.access-control.roles.destroy', $webRole))->assertNotFound();
        $this->assertDatabaseHas('roles', ['id' => $webRole->id, 'name' => 'Customer Role', 'guard_name' => 'web']);
    }

    public function test_access_control_directory_is_scoped_and_paginated(): void
    {
        $admin = $this->adminWithUserManagementPermission();
        $customer = User::factory()->create(['role' => 'user']);
        $adminRole = Role::where('name', 'User Manager')->firstOrFail();
        foreach (range(1, 26) as $number) {
            $listedAdmin = User::factory()->create(['role' => 'admin']);
            $listedAdmin->assignRole($adminRole);
        }

        $response = $this->actingAs($admin, 'admin')->get(route('admin.access-control.index'));

        $response->assertOk()
            ->assertSee($admin->email)
            ->assertDontSee($customer->email)
            ->assertSee('users_page=');
    }

    public function test_deactivated_admin_cannot_login_or_keep_an_admin_session(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => false,
            'email' => 'disabled-admin@example.test',
        ]);

        $this->post(route('admin.login.post'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('admin');

        $customer = User::factory()->create(['is_active' => true]);
        $this->actingAs($customer, 'web')->actingAs($admin, 'admin')
            ->get(route('admin.index'))
            ->assertRedirect(route('signin'));

        $this->assertGuest('admin');
        $this->assertAuthenticatedAs($customer, 'web');
    }

    private function adminWithUserManagementPermission(): User
    {
        Permission::create(['name' => 'manage users', 'guard_name' => 'admin']);
        $role = Role::create(['name' => 'User Manager', 'guard_name' => 'admin']);
        $role->givePermissionTo('manage users');
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $admin->assignRole($role);

        return $admin;
    }
}
