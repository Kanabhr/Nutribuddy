<?php

namespace Tests\Feature;

use App\Events\OrderPaid;
use App\Models\Order;
use App\Models\OrderPaymentAdjustment;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class OrderPaymentAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->app[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function test_admin_can_mark_a_pending_order_paid_with_reason_and_reference(): void
    {
        Event::fake([OrderPaid::class]);
        $admin = $this->adminWith(['manage orders', 'manage administration'], 'Meera Admin');
        $order = $this->order(['payment_status' => 'pending']);
        $payment = Payment::create(['order_id' => $order->id, 'provider' => 'razorpay', 'status' => 'initiated', 'amount' => 1000]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.ecommerce.orders.payment-adjustments.store', $order), $this->payload('mark_paid'))
            ->assertRedirect()
            ->assertSessionHas('success', 'Payment adjustment recorded.');

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertDatabaseHas('order_payment_adjustments', [
            'order_id' => $order->id,
            'type' => 'mark_paid',
            'amount' => '1000.00',
            'from_payment_status' => 'pending',
            'to_payment_status' => 'paid',
            'reference' => 'UTR123456',
            'adjusted_by' => $admin->id,
            'adjusted_by_name' => 'Meera Admin',
        ]);
        // Gateway transactions stay untouched.
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'initiated']);
        // The order's Status Timeline gets an entry with the admin and the remark.
        $this->assertDatabaseHas('order_status_histories', ['order_id' => $order->id, 'updated_by' => $admin->id]);
        $this->assertStringContainsString('UTR123456', (string) $order->statusHistories()->latest('id')->first()->note);
        Event::assertDispatchedTimes(OrderPaid::class, 1);
    }

    public function test_paid_and_cancelled_orders_cannot_be_marked_paid(): void
    {
        $admin = $this->adminWith(['manage orders', 'manage administration']);
        $alreadyPaid = $this->order(['payment_status' => 'paid']);
        $cancelled = $this->order(['payment_status' => 'pending', 'status' => 'cancelled']);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.ecommerce.orders.payment-adjustments.store', $alreadyPaid), $this->payload('mark_paid'))
            ->assertSessionHas('error', 'Only pending or failed payments can be marked as paid.');
        $this->actingAs($admin, 'admin')
            ->post(route('admin.ecommerce.orders.payment-adjustments.store', $cancelled), $this->payload('mark_paid'))
            ->assertSessionHas('error', 'A cancelled order cannot be marked as paid.');

        $this->assertDatabaseCount('order_payment_adjustments', 0);
    }

    public function test_partial_refunds_add_up_and_a_full_refund_takes_the_rest(): void
    {
        $admin = $this->adminWith(['manage orders', 'manage administration']);
        $order = $this->order(['payment_status' => 'paid', 'grand_total' => 1000]);
        $url = route('admin.ecommerce.orders.payment-adjustments.store', $order);

        $this->actingAs($admin, 'admin')->post($url, $this->payload('partial_refund', ['amount' => 300]))->assertSessionHas('success');
        $this->assertSame('partially_refunded', $order->fresh()->payment_status);

        $this->actingAs($admin, 'admin')->post($url, $this->payload('partial_refund', ['amount' => 400]))->assertSessionHas('success');

        // 300 is all that is left, so a "partial" refund of 400 (or 300) must be rejected.
        $this->actingAs($admin, 'admin')->post($url, $this->payload('partial_refund', ['amount' => 400]))
            ->assertSessionHas('error', 'A partial refund must be less than the remaining refundable amount (INR 300.00). Use a full refund instead.');

        $this->actingAs($admin, 'admin')->post($url, $this->payload('refund'))->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('refunded', $order->payment_status);
        $this->assertSame('300.00', OrderPaymentAdjustment::where('order_id', $order->id)->where('type', 'refund')->value('amount'));
        $this->assertSame(1000.0, (float) OrderPaymentAdjustment::where('order_id', $order->id)->whereIn('type', ['refund', 'partial_refund'])->sum('amount'));

        // Nothing more can be refunded once the order is refunded.
        $this->actingAs($admin, 'admin')->post($url, $this->payload('refund'))
            ->assertSessionHas('error', 'Only paid or partially refunded orders can be refunded.');
    }

    public function test_reason_reference_and_confirmation_are_required(): void
    {
        $admin = $this->adminWith(['manage orders', 'manage administration']);
        $order = $this->order(['payment_status' => 'pending']);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.ecommerce.orders.payment-adjustments.store', $order), ['type' => 'mark_paid', 'reason' => 'short'])
            ->assertSessionHasErrors(['reason', 'reference', 'confirm']);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.ecommerce.orders.payment-adjustments.store', $order), $this->payload('partial_refund', ['amount' => null]))
            ->assertSessionHasErrors(['amount']);

        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertDatabaseCount('order_payment_adjustments', 0);
    }

    public function test_admin_without_the_payments_permission_is_refused(): void
    {
        $admin = $this->adminWith(['manage orders']); // manage orders alone is not enough
        $order = $this->order(['payment_status' => 'pending']);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.ecommerce.orders.payment-adjustments.store', $order), $this->payload('mark_paid'))
            ->assertForbidden();

        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_the_ledger_cannot_be_edited_or_deleted(): void
    {
        $order = $this->order(['payment_status' => 'paid']);
        $adjustment = OrderPaymentAdjustment::create([
            'order_id' => $order->id,
            'type' => 'partial_refund',
            'amount' => 100,
            'from_payment_status' => 'paid',
            'to_payment_status' => 'partially_refunded',
            'reason' => 'Customer returned one item',
            'reference' => 'REF-1',
            'adjusted_by_name' => 'Someone',
            'created_at' => now(),
        ]);

        $this->assertFalse($adjustment->update(['reason' => 'Changed afterwards']));
        $this->assertFalse($adjustment->delete());
        $this->assertDatabaseHas('order_payment_adjustments', ['id' => $adjustment->id, 'reason' => 'Customer returned one item']);
    }

    public function test_the_order_status_form_can_no_longer_change_the_payment_status(): void
    {
        $admin = $this->adminWith(['manage orders']);
        $order = $this->order(['status' => 'pending', 'payment_status' => 'pending']);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.ecommerce.orders.update-status', $order), ['status' => 'confirmed', 'payment_status' => 'paid'])
            ->assertRedirect();

        $order->refresh();
        $this->assertSame('confirmed', $order->status);
        $this->assertSame('pending', $order->payment_status);
    }

    public function test_order_page_lists_manual_adjustments_with_the_admin_name(): void
    {
        // Needs the include added to orders/show.blade.php (see PASTE-GUIDE.md).
        $this->withoutVite();
        $admin = $this->adminWith(['manage orders', 'manage administration'], 'Meera Admin');
        $order = $this->order(['payment_status' => 'pending']);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.ecommerce.orders.payment-adjustments.store', $order), $this->payload('mark_paid', ['reason' => 'Bank transfer received on 20 Nov']));

        $this->actingAs($admin, 'admin')
            ->get(route('admin.ecommerce.orders.show', $order))
            ->assertOk()
            ->assertSee('Manual adjustments')
            ->assertSee('Bank transfer received on 20 Nov')
            ->assertSee('Meera Admin')
            ->assertSee('UTR123456');
    }

    /** @param array<string, mixed> $overrides */
    private function payload(string $type, array $overrides = []): array
    {
        return array_merge([
            'type' => $type,
            'reason' => 'Customer paid by bank transfer',
            'reference' => 'UTR123456',
            'confirm' => '1',
        ], $overrides);
    }

    /** @param array<string, mixed> $attributes */
    private function order(array $attributes = []): Order
    {
        return Order::factory()->create(array_merge([
            'status' => 'confirmed',
            'payment_method' => 'razorpay',
            'grand_total' => 1000,
        ], $attributes));
    }

    /** @param list<string> $permissions */
    private function adminWith(array $permissions, string $name = 'Test Admin'): User
    {
        foreach (['manage orders', 'view orders', 'manage administration'] as $permission) {
            Permission::findOrCreate($permission, 'admin');
        }

        $role = Role::create(['name' => 'Role '.uniqid(), 'guard_name' => 'admin']);
        $role->givePermissionTo($permissions);
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true, 'name' => $name]);
        $admin->assignRole($role);

        return $admin;
    }
}