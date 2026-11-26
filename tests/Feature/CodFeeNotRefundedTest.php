<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * R12 (tests only): the COD fee and shipping are never part of a return refund.
 * These tests do not assert the full refund formula, only that those two amounts are not included.
 */
class CodFeeNotRefundedTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Order $order;

    private OrderItem $item;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->user = User::factory()->create();
        // Products Rs 300 + shipping Rs 50 + COD fee Rs 30 = Rs 380 paid in cash on delivery.
        $this->order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'delivered',
            'fulfillment_status' => 'fulfilled',
            'payment_method' => 'cod',
            'payment_status' => 'paid',
            'subtotal' => 300,
            'shipping_total' => 50,
            'grand_total' => 380,
            'pricing_snapshot' => ['cod_fee' => 30, 'online_switch_discount' => 0, 'coins_earned' => 15],
        ]);
        $this->item = OrderItem::factory()->create([
            'order_id' => $this->order->id,
            'product_name' => 'Nutri Gummies',
            'sku' => 'NG-001',
            'quantity' => 3,
            'unit_price' => 100,
            'line_total' => 300,
        ]);

        $this->actingAs($this->user);
    }

    private function requestReturn(int $quantity): OrderReturn
    {
        $this->postJson(route('user.orders.returns.store', $this->order), [
            'reason' => 'Product taste is not suitable for my child.',
            'items' => [['order_item_id' => $this->item->id, 'quantity' => $quantity]],
        ])->assertCreated();

        return OrderReturn::query()->latest('id')->firstOrFail();
    }

    public function test_full_return_refund_does_not_include_cod_fee_or_shipping(): void
    {
        $return = $this->requestReturn(3);

        $refund = (float) $return->refund_amount;
        $this->assertLessThanOrEqual(300.0, $refund);                      // products only
        $this->assertLessThanOrEqual(380.0 - 30.0 - 50.0, $refund);        // grand total minus fee minus shipping
        $this->assertLessThan((float) $this->order->grand_total, $refund);
    }

    public function test_partial_return_refund_does_not_include_cod_fee_or_shipping(): void
    {
        $return = $this->requestReturn(1);

        $refund = (float) $return->refund_amount;
        $this->assertLessThanOrEqual(100.0, $refund); // one unit of products only; no share of fee or shipping
    }

    public function test_the_cod_fee_stays_on_the_order_after_a_return(): void
    {
        $this->requestReturn(3);

        $order = $this->order->fresh();
        $this->assertEquals(30, data_get($order->pricing_snapshot, 'cod_fee'));
        $this->assertEquals(380.0, (float) $order->grand_total);
    }
}
