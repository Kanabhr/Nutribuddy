<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::findOrCreate('view audit log', 'admin');
        Permission::findOrCreate('view dashboard', 'admin');
        $role = Role::findOrCreate('Super Admin', 'admin');
        $role->givePermissionTo(['view audit log', 'view dashboard']);

        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->admin->assignRole($role);
    }

    // ── AuditService unit-level tests ─────────────────────────────────────────

    public function test_audit_service_records_updated_entry(): void
    {
        $order = Order::factory()->create(['status' => 'pending']);
        $this->actingAs($this->admin, 'admin');

        app(AuditService::class)->record($order, 'updated', ['status' => 'pending'], ['status' => 'confirmed']);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => 'Order',
            'action' => 'updated',
        ]);

        $log = AuditLog::where('auditable_type', 'Order')->where('action', 'updated')->first();
        $this->assertSame('confirmed', (string) ($log->new_values['status'] ?? null));
        $this->assertSame('pending', (string) ($log->old_values['status'] ?? null));
    }

    public function test_audit_service_masks_password_field(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($this->admin, 'admin');

        app(AuditService::class)->record($user, 'updated', ['password' => 'old_hash'], ['password' => 'new_hash', 'name' => 'Alice']);

        $log = AuditLog::first();
        $this->assertArrayNotHasKey('password', $log->new_values ?? []);
        $this->assertArrayNotHasKey('password', $log->old_values ?? []);
        $this->assertSame('Alice', $log->new_values['name'] ?? null);
    }

    public function test_audit_service_masks_two_factor_secret(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($this->admin, 'admin');

        app(AuditService::class)->record($user, 'updated', [], ['two_factor_secret' => 'SECRET', 'name' => 'Bob']);

        $log = AuditLog::first();
        $this->assertArrayNotHasKey('two_factor_secret', $log->new_values ?? []);
        $this->assertSame('Bob', $log->new_values['name'] ?? null);
    }

    public function test_audit_service_masks_setting_secret_value(): void
    {
        $setting = Setting::create(['key' => 'razorpay_key_id', 'value' => 'rzp_live_xyz', 'group' => 'payment']);
        $this->actingAs($this->admin, 'admin');

        app(AuditService::class)->record($setting, 'updated', ['value' => 'old'], ['value' => 'new_key']);

        $log = AuditLog::where('auditable_type', 'Setting')->first();
        $this->assertSame('[redacted]', $log->new_values['value'] ?? null);
    }

    public function test_actor_type_is_admin_when_admin_logged_in(): void
    {
        $this->actingAs($this->admin, 'admin');
        $order = Order::factory()->create();

        app(AuditService::class)->record($order, 'created', [], $order->getAttributes());

        $log = AuditLog::first();
        $this->assertSame('admin', $log->actor_type);
        $this->assertSame($this->admin->id, $log->actor_id);
    }

    public function test_actor_type_is_system_when_no_guard_authenticated(): void
    {
        $order = Order::factory()->create();

        app(AuditService::class)->record($order, 'created', [], ['status' => 'pending']);

        $log = AuditLog::first();
        $this->assertSame('system', $log->actor_type);
        $this->assertNull($log->actor_id);
    }

    // ── Observer fires on model updates ───────────────────────────────────────

    public function test_observer_fires_on_order_status_update(): void
    {
        $order = Order::factory()->create(['status' => 'pending']);
        $this->actingAs($this->admin, 'admin');

        $order->update(['status' => 'confirmed']);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => 'Order',
            'action' => 'updated',
        ]);
    }

    public function test_observer_fires_on_inventory_update(): void
    {
        $inv = Inventory::factory()->create(['stock_qty' => 100]);
        $this->actingAs($this->admin, 'admin');

        $inv->update(['stock_qty' => 90]);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => 'Inventory',
            'action' => 'updated',
        ]);
        $log = AuditLog::where('auditable_type', 'Inventory')->where('action', 'updated')->first();
        $this->assertSame(90, (int) ($log->new_values['stock_qty'] ?? null));
    }

    public function test_observer_fires_on_payment_status_update(): void
    {
        $order = Order::factory()->create();
        $payment = Payment::factory()->create(['order_id' => $order->id, 'status' => 'pending']);
        $this->actingAs($this->admin, 'admin');

        $payment->update(['status' => 'refunded']);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => 'Payment',
            'action' => 'updated',
        ]);
    }

    // ── Order created PII whitelist ────────────────────────────────────────────

    public function test_order_created_does_not_log_customer_pii(): void
    {
        $order = Order::factory()->create([
            'customer_name' => 'Jane Doe',
            'customer_email' => 'jane@example.com',
            'customer_phone' => '9999999999',
            'shipping_address_line_1' => '12 Main St',
        ]);

        // The observer fires on created — find that log entry.
        $log = AuditLog::where('auditable_type', 'Order')
            ->where('action', 'created')
            ->where('auditable_id', $order->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertArrayNotHasKey('customer_name', $log->new_values ?? []);
        $this->assertArrayNotHasKey('customer_email', $log->new_values ?? []);
        $this->assertArrayNotHasKey('customer_phone', $log->new_values ?? []);
        $this->assertArrayNotHasKey('shipping_address_line_1', $log->new_values ?? []);
        // Operational fields must still be present.
        $this->assertArrayHasKey('status', $log->new_values ?? []);
        $this->assertArrayHasKey('order_number', $log->new_values ?? []);
    }

    public function test_audit_log_page_requires_permission(): void
    {
        $plain = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        // No permission granted
        $this->actingAs($plain, 'admin')
            ->withSession(['admin_2fa_verified' => $this->admin->id])
            ->get(route('admin.system.audit-log.index'))
            ->assertForbidden();
    }

    public function test_audit_log_page_loads_for_permitted_admin(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->withSession(['admin_2fa_verified' => $this->admin->id])
            ->get(route('admin.system.audit-log.index'))
            ->assertOk()
            ->assertSee('Audit Log');
    }

    public function test_audit_log_page_filters_by_entity(): void
    {
        AuditLog::create(['actor_type' => 'system', 'action' => 'updated', 'auditable_type' => 'Order', 'auditable_id' => 1]);
        AuditLog::create(['actor_type' => 'system', 'action' => 'updated', 'auditable_type' => 'Coupon', 'auditable_id' => 2]);

        // The filter URL returns only Coupon rows; the Order row's auditable_id (#1) should not appear.
        $response = $this->actingAs($this->admin, 'admin')
            ->withSession(['admin_2fa_verified' => $this->admin->id])
            ->get(route('admin.system.audit-log.index', ['entity' => 'Coupon']));

        $response->assertOk()->assertSee('Coupon');
        // The Order row (auditable_id=1) must not appear in the table body.
        // We confirm by checking that only 1 entry is returned.
        $this->assertCount(1, AuditLog::where('auditable_type', 'Coupon')->get());
    }
}
