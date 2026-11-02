<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReturnWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_quantity_based_return_and_over_return_is_blocked(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'delivered',
            'fulfillment_status' => 'fulfilled',
            'payment_method' => 'cod',
            'payment_status' => 'paid',
        ]);
        $item = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_name' => 'Nutri Gummies',
            'sku' => 'NG-001',
            'quantity' => 3,
            'unit_price' => 100,
            'line_total' => 300,
        ]);

        $this->actingAs($user);

        $this->postJson(route('user.orders.returns.store', $order), [
            'reason' => 'Product taste is not suitable for my child.',
            'items' => [
                ['order_item_id' => $item->id, 'quantity' => 2],
            ],
        ])->assertCreated();

        $this->assertDatabaseCount('order_returns', 1);
        $this->assertDatabaseHas('order_return_items', [
            'order_item_id' => $item->id,
            'quantity' => 2,
        ]);

        $this->postJson(route('user.orders.returns.store', $order), [
            'reason' => 'Trying to return more than remaining quantity.',
            'items' => [
                ['order_item_id' => $item->id, 'quantity' => 2],
            ],
        ])->assertStatus(422);

        $this->assertDatabaseCount('order_returns', 1);
    }

    public function test_user_can_return_remaining_quantity_in_second_request(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'delivered',
            'fulfillment_status' => 'fulfilled',
            'payment_method' => 'cod',
            'payment_status' => 'paid',
        ]);
        $item = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_name' => 'Nutri Gummies',
            'sku' => 'NG-001',
            'quantity' => 3,
            'unit_price' => 100,
            'line_total' => 300,
        ]);

        $this->actingAs($user);

        $this->postJson(route('user.orders.returns.store', $order), [
            'reason' => 'First partial return request.',
            'items' => [
                ['order_item_id' => $item->id, 'quantity' => 2],
            ],
        ])->assertCreated();

        $this->postJson(route('user.orders.returns.store', $order), [
            'reason' => 'Returning remaining quantity now.',
            'items' => [
                ['order_item_id' => $item->id, 'quantity' => 1],
            ],
        ])->assertCreated();

        $this->assertDatabaseCount('order_returns', 2);
        $this->assertDatabaseHas('order_return_items', [
            'order_item_id' => $item->id,
            'quantity' => 1,
        ]);
    }

    public function test_return_cannot_include_an_item_from_another_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'delivered',
        ]);
        $otherOrder = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'delivered',
        ]);
        $otherItem = OrderItem::factory()->create([
            'order_id' => $otherOrder->id,
            'product_name' => 'Other order item',
            'sku' => 'OTHER-001',
            'quantity' => 1,
            'line_total' => 100,
        ]);

        $this->actingAs($user)
            ->postJson(route('user.orders.returns.store', $order), [
                'reason' => 'Returning wrong order item.',
                'items' => [['order_item_id' => $otherItem->id, 'quantity' => 1]],
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Invalid item selected.')
            ->assertJsonPath('errors.items.0', 'Invalid item selected.');

        $this->assertDatabaseCount('order_returns', 0);
        $this->assertDatabaseCount('order_return_items', 0);
    }

    public function test_admin_returns_index_and_show_render_when_order_user_is_null(): void
    {
        $permission = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'view orders', 'guard_name' => 'admin']);
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->givePermissionTo($permission);

        $order = Order::factory()->create([
            'user_id' => null,
            'customer_name' => 'Adarsh Vishwakarma',
            'customer_email' => 'adarsh@example.com',
            'status' => 'delivered',
            'grand_total' => 500,
        ]);

        $orderReturn = \App\Models\OrderReturn::create([
            'order_id' => $order->id,
            'user_id' => null,
            'return_number' => 'RET-TEST-001',
            'status' => 'pending',
            'reason' => 'Defective product',
            'refund_amount' => 500,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.ecommerce.order-returns.index'))
            ->assertOk()
            ->assertSee('RET-TEST-001')
            ->assertSee('Adarsh Vishwakarma');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.ecommerce.order-returns.show', $orderReturn))
            ->assertOk()
            ->assertSee('RET-TEST-001')
            ->assertSee('Adarsh Vishwakarma');
    }
}
