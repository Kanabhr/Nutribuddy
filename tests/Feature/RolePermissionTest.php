<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Access\RolePermissionSyncService;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected $adminRole;
    protected $staffRole;
    protected $unauthorizedRole;
    
    protected $adminUser;
    protected $staffUser;
    protected $unauthorizedUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Force SQLite in-memory database for absolute safety as requested by user
        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');

        // Clear Spatie cache
        $this->app[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions for the 'admin' guard
        $permissions = [
            'view dashboard', 'manage dashboard',
            'view diet chart', 'manage diet chart',
            'view products', 'manage products'
        ];

        foreach ($permissions as $perm) {
            Permission::create(['name' => $perm, 'guard_name' => 'admin']);
        }

        // Create Roles
        $this->adminRole = Role::create(['name' => 'Admin Test', 'guard_name' => 'admin']);
        $this->adminRole->givePermissionTo($permissions); // All permissions

        $this->staffRole = Role::create(['name' => 'Staff Test', 'guard_name' => 'admin']);
        $this->staffRole->givePermissionTo(['view dashboard', 'view diet chart']); // Read Only on 2 modules

        $this->unauthorizedRole = Role::create(['name' => 'Unauthorized Test', 'guard_name' => 'admin']);
        // No permissions

        // Create Users
        $this->adminUser = User::factory()->create(['role' => 'admin', 'is_active' => 1]);
        $this->adminUser->assignRole($this->adminRole);

        $this->staffUser = User::factory()->create(['role' => 'admin', 'is_active' => 1]);
        $this->staffUser->assignRole($this->staffRole);

        $this->unauthorizedUser = User::factory()->create(['role' => 'admin', 'is_active' => 1]);
        $this->unauthorizedUser->assignRole($this->unauthorizedRole);
    }

    /** @test */
    public function database_and_seeding_verification()
    {
        $this->assertDatabaseHas('roles', ['name' => 'Admin Test', 'guard_name' => 'admin']);
        $this->assertDatabaseHas('permissions', ['name' => 'manage diet chart', 'guard_name' => 'admin']);
        
        // Ensure standard Spatie associations work
        $this->assertTrue($this->adminUser->hasRole('Admin Test', 'admin'));
        $this->assertTrue($this->staffUser->hasRole('Staff Test', 'admin'));
    }

    /** @test */
    public function service_and_backend_logic_layer()
    {
        // Admin user should have all permissions
        $this->assertTrue($this->adminUser->hasPermissionTo('manage diet chart', 'admin'));
        $this->assertTrue($this->adminUser->hasPermissionTo('view diet chart', 'admin'));

        // Staff user should have limited permissions
        $this->assertTrue($this->staffUser->hasPermissionTo('view diet chart', 'admin'));
        $this->assertFalse($this->staffUser->hasPermissionTo('manage diet chart', 'admin'));

        // Unauthorized user should have none
        $this->assertFalse($this->unauthorizedUser->hasPermissionTo('view diet chart', 'admin'));

        // Testing dynamic assignment programmatically
        $this->unauthorizedRole->givePermissionTo('view products');
        $this->assertTrue($this->unauthorizedUser->hasPermissionTo('view products', 'admin'));
    }

    /** @test */
    public function role_permission_sync_service_preserves_manage_implies_view(): void
    {
        $role = Role::create(['name' => 'Sync Test', 'guard_name' => 'admin']);

        app(RolePermissionSyncService::class)->sync($role, [
            'products' => ['manage' => '1'],
        ]);

        $this->assertTrue($role->fresh()->hasPermissionTo('manage products', 'admin'));
        $this->assertTrue($role->fresh()->hasPermissionTo('view products', 'admin'));
    }

    /** @test */
    public function route_protection_layer_unauthenticated()
    {
        // Guests trying to access admin dashboard should be redirected to login
        $response = $this->get(route('admin.index'));
        $response->assertStatus(302);
    }

    /** @test */
    public function controller_and_form_request_layer_requires_authorization()
    {
        // -------------------------------------------------------------
        // NOTE: This test will currently FAIL if your controllers 
        // do not have Gate::authorize() checks in their methods!
        // This is part of the audit process.
        // -------------------------------------------------------------

        // Test with unauthorized user accessing a protected route
        $response = $this->actingAs($this->unauthorizedUser, 'admin')->get(route('admin.ecommerce.diet-plans.index'));
        
        // This *should* be 403 Forbidden. If it returns 200, the route is unprotected!
        $response->assertStatus(403); 
    }

    /** @test */
    public function view_blade_directives_and_sidebar_layer()
    {
        // Authenticate as staff user who has 'view diet chart' but NOT 'view products'
        $response = $this->actingAs($this->staffUser, 'admin')->get(route('admin.index'));
        
        $response->assertStatus(200);

        // Sidebar should contain Dashboard (because of view dashboard perm)
        $response->assertSee('Dashboard');

        // Sidebar should contain Diet Chart (because of view diet chart perm)
        $response->assertSee('Diet Chart');

        // Sidebar should NOT contain Products Management (because lacks view products perm)
        $response->assertDontSee('Products Management');
    }

    public function test_admin_without_dashboard_permission_cannot_view_dashboard(): void
    {
        $this->actingAs($this->unauthorizedUser, 'admin')
            ->get(route('admin.index'))
            ->assertForbidden();
    }
}
