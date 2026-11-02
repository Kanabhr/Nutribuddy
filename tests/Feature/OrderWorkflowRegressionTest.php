<?php

namespace Tests\Feature;

use App\Events\OrderPaid;
use App\Mail\OrderStatusUpdatedMail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\OrderReturnItem;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class OrderWorkflowRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app[PermissionRegistrar::class]->forgetCachedPermissions();
        Mail::fake();
    }

    public function test_admin_delivery_marks_cod_payment_paid_and_records_history(): void
    {
        Event::fake([OrderPaid::class]);
        $admin = $this->adminWithPermission('manage orders');
        $order = Order::factory()->create([
            'status' => 'shipped',
            'fulfillment_status' => 'unfulfilled',
            'payment_method' => 'cod',
            'payment_status' => 'pending',
        ]);
        $payment = Payment::create([
            'order_id' => $order->id,
            'provider' => 'cod',
            'status' => 'initiated',
            'amount' => 250,
        ]);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.ecommerce.orders.update-status', $order), ['status' => 'delivered'])
            ->assertRedirect()
            ->assertSessionHas('success', 'Order status updated successfully.');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'delivered',
            'fulfillment_status' => 'unfulfilled',
            'payment_status' => 'paid',
        ]);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'paid']);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => 'shipped',
            'to_status' => 'delivered',
            'updated_by' => $admin->id,
        ]);
        Event::assertDispatchedTimes(OrderPaid::class, 1);
        Mail::assertQueued(OrderStatusUpdatedMail::class, fn (OrderStatusUpdatedMail $mail) => $mail->order->status === 'delivered');
    }

    public function test_customer_cancellation_updates_order_and_history(): void
    {
        $customer = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'pending',
            'fulfillment_status' => 'unfulfilled',
        ]);

        $this->actingAs($customer)
            ->patchJson(route('user.orders.cancel', $order))
            ->assertOk()
            ->assertJsonPath('message', 'Order cancelled successfully.')
            ->assertJsonPath('order.status', 'cancelled');

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => 'pending',
            'to_status' => 'cancelled',
            'updated_by' => $customer->id,
        ]);
        Mail::assertQueued(OrderStatusUpdatedMail::class, fn (OrderStatusUpdatedMail $mail) => $mail->order->status === 'cancelled');
    }

    public function test_completing_all_returned_quantities_marks_cod_order_refunded(): void
    {
        $admin = $this->adminWithPermission('manage orders');
        $order = Order::factory()->create([
            'status' => 'delivered',
            'fulfillment_status' => 'fulfilled',
            'payment_method' => 'cod',
            'payment_status' => 'paid',
        ]);
        $item = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_name' => 'Test product',
            'sku' => 'TEST-001',
            'quantity' => 1,
            'unit_price' => 250,
            'line_total' => 250,
        ]);
        $orderReturn = OrderReturn::create([
            'order_id' => $order->id,
            'return_number' => 'RET-TEST-001',
            'reason' => 'Damaged product',
            'status' => 'approved',
            'refund_amount' => 250,
        ]);
        OrderReturnItem::create([
            'order_return_id' => $orderReturn->id,
            'order_item_id' => $item->id,
            'product_name' => $item->product_name,
            'sku' => $item->sku,
            'quantity' => 1,
            'unit_price' => 250,
            'line_total' => 250,
        ]);
        $payment = Payment::create([
            'order_id' => $order->id,
            'provider' => 'cod',
            'status' => 'paid',
            'amount' => 250,
        ]);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.ecommerce.order-returns.update', $orderReturn), [
                'status' => 'completed',
                'refund_amount' => 250,
            ])
            ->assertRedirect(route('admin.ecommerce.order-returns.index'));

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'returned',
            'payment_status' => 'refunded',
        ]);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'refunded']);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'to_status' => 'returned',
            'updated_by' => $admin->id,
        ]);
    }

    private function adminWithPermission(string $permission): User
    {
        Permission::create(['name' => $permission, 'guard_name' => 'admin']);
        $role = Role::create(['name' => 'Order Manager '.$permission, 'guard_name' => 'admin']);
        $role->givePermissionTo($permission);
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $admin->assignRole($role);

        return $admin;
    }
}
