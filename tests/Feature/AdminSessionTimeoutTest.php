<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminSessionTimeoutTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::findOrCreate('view dashboard', 'admin');
        $role = Role::findOrCreate('Super Admin', 'admin');
        $role->givePermissionTo('view dashboard');

        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->admin->assignRole($role);
    }

    public function test_active_session_passes_through(): void
    {
        Config::set('admin.session_timeout', 120);

        $this->actingAs($this->admin, 'admin')
            ->withSession([
                'admin_last_activity' => time() - 60,  // 1 min ago — within timeout
                'admin_2fa_verified' => $this->admin->id,
            ])
            ->get(route('admin.index'))
            ->assertOk();
    }

    public function test_expired_session_redirects_to_signin(): void
    {
        Config::set('admin.session_timeout', 1); // 1 minute

        $this->actingAs($this->admin, 'admin')
            ->withSession([
                'admin_last_activity' => time() - 120, // 2 minutes ago — expired
                'admin_2fa_verified' => $this->admin->id,
            ])
            ->get(route('admin.index'))
            ->assertRedirect(route('signin'));
    }

    public function test_expired_session_shows_message(): void
    {
        Config::set('admin.session_timeout', 1);

        $response = $this->actingAs($this->admin, 'admin')
            ->withSession([
                'admin_last_activity' => time() - 120,
                'admin_2fa_verified' => $this->admin->id,
            ])
            ->get(route('admin.index'));

        // Middleware redirects and flashes 'error'; signin view now renders it.
        $response->assertRedirect(route('signin'));
        $response->assertSessionHas('error');

        // Follow through to confirm the message renders on the page.
        $this->followingRedirects()
            ->actingAs($this->admin, 'admin')
            ->withSession([
                'admin_last_activity' => time() - 120,
                'admin_2fa_verified' => $this->admin->id,
            ])
            ->get(route('admin.index'))
            ->assertSee('session expired', false);
    }

    public function test_timeout_does_not_affect_frontend_users(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);

        // Frontend users never have admin guard active — middleware should be a no-op.
        // The admin guard is not authenticated so the timeout middleware returns early.
        $this->actingAs($customer, 'web')
            ->withSession(['admin_last_activity' => time() - 9999])
            ->get('/') // public homepage — always reachable
            ->assertStatus(200);
    }

    public function test_no_last_activity_does_not_expire_on_first_request(): void
    {
        // First request: no admin_last_activity key yet. Should not expire.
        Config::set('admin.session_timeout', 1);

        $this->actingAs($this->admin, 'admin')
            ->withSession(['admin_2fa_verified' => $this->admin->id])
            ->get(route('admin.index'))
            ->assertOk();
    }

    public function test_inactivity_timeout_clears_2fa_flag(): void
    {
        Config::set('admin.session_timeout', 1);

        $this->actingAs($this->admin, 'admin')
            ->withSession([
                'admin_last_activity' => time() - 120,
                'admin_2fa_verified' => $this->admin->id,
            ])
            ->get(route('admin.index'))
            ->assertRedirect(route('signin'));

        // The 2FA verified flag must be gone from the session.
        $this->assertNull(session('admin_2fa_verified'));
    }
}
