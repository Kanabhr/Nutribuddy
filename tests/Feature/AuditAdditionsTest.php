<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\User;
use App\Services\Access\RolePermissionSyncService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/** Point 28 additions: product edits, role and permission changes, successful admin logins (not failed logins). */
class AuditAdditionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function test_product_edits_are_audited_and_long_text_is_trimmed(): void
    {
        $product = Product::factory()->create();
        DB::table('audit_logs')->delete();

        $product->forceFill(['name' => 'Renamed Product', 'description' => str_repeat('x', 900)])->save();

        $log = AuditLog::where('auditable_type', 'Product')->where('action', 'updated')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('Renamed Product', $log->new_values['name']);
        $this->assertLessThanOrEqual(503, mb_strlen($log->new_values['description']));
    }

    public function test_role_creation_and_permission_changes_are_audited(): void
    {
        $role = Role::create(['name' => 'Catalog Editor', 'guard_name' => 'admin']);
        $this->assertDatabaseHas('audit_logs', ['auditable_type' => 'Role', 'auditable_id' => $role->id, 'action' => 'created']);

        app(RolePermissionSyncService::class)->sync($role, ['orders' => ['view' => '1']]);

        $log = AuditLog::where('auditable_type', 'Role')->where('action', 'permissions_changed')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame([], $log->old_values['permissions']);
        $this->assertSame(['view orders'], $log->new_values['permissions']);
    }

    public function test_successful_admin_login_is_audited(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        event(new Login('admin', $admin, false));

        $this->assertDatabaseHas('audit_logs', ['auditable_type' => 'User', 'auditable_id' => $admin->id, 'action' => 'login']);
    }

    public function test_failed_logins_and_customer_logins_are_not_audited(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $customer = User::factory()->create(['role' => 'customer']);
        DB::table('audit_logs')->delete();

        event(new Failed('admin', $admin, ['email' => $admin->email]));
        event(new Login('web', $customer, false));

        $this->assertDatabaseMissing('audit_logs', ['action' => 'login']);
    }
}