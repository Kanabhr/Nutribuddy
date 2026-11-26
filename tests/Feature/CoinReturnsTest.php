<?php

namespace Tests\Feature;

use App\Events\OrderPaid;
use App\Models\CoinTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\OrderStatusHistory;
use App\Models\User;
use App\Services\Ecommerce\OrderReturnAdminService;
use App\Services\Loyalty\CoinLedgerService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** R4: 7-day return window and proportional cancelling of Pending coins on completed returns. */
class CoinReturnsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Order $order;

    private OrderItem $item;

    private Carbon $deliveredAt;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->deliveredAt = Carbon::parse('2026-12-01 10:00:00');

        $this->user = User::factory()->create();
        $this->order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'delivered',
            'fulfillment_status' => 'fulfilled',
            'payment_method' => 'cod',
            'payment_status' => 'paid',
            'subtotal' => 400,
            'shipping_total' => 50,
            'grand_total' => 480,
            'pricing_snapshot' => ['coins_earned' => 20, 'cod_fee' => 30],
        ]);
        $this->item = OrderItem::factory()->create([
            'order_id' => $this->order->id, 'product_name' => 'Test product', 'sku' => 'TEST-SKU', 'quantity' => 4, 'unit_price' => 100, 'line_total' => 400,
        ]);
        event(new OrderPaid($this->order));

        OrderStatusHistory::create(['order_id' => $this->order->id, 'from_status' => 'shipped', 'to_status' => 'delivered']);
        OrderStatusHistory::query()->where('order_id', $this->order->id)->update(['created_at' => $this->deliveredAt]);

        $this->actingAs($this->user);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function requestReturn(int $qty)
    {
        return $this->postJson(route('user.orders.returns.store', $this->order), [
            'reason' => 'Product taste is not suitable for my child.',
            'items' => [['order_item_id' => $this->item->id, 'quantity' => $qty]],
        ]);
    }

    private function complete(OrderReturn $return): void
    {
        app(OrderReturnAdminService::class)->updateStatus($return, [
            'status' => 'completed', 'refund_amount' => $return->refund_amount, 'admin_note' => null,
        ], 1);
    }

    private function lot(): CoinTransaction
    {
        return CoinTransaction::query()->where('order_id', $this->order->id)->where('type', 'earned')->firstOrFail();
    }

    public function test_return_is_allowed_exactly_at_seven_days(): void
    {
        Carbon::setTestNow($this->deliveredAt->copy()->addDays(7));
        $this->requestReturn(1)->assertCreated();
    }

    public function test_return_is_refused_one_second_after_seven_days(): void
    {
        Carbon::setTestNow($this->deliveredAt->copy()->addDays(7)->addSecond());
        $this->requestReturn(1)->assertStatus(422);
        $this->assertSame(0, OrderReturn::query()->count());
    }

    public function test_order_without_delivered_history_is_not_blocked(): void
    {
        OrderStatusHistory::query()->where('order_id', $this->order->id)->delete();
        Carbon::setTestNow($this->deliveredAt->copy()->addDays(60));
        $this->requestReturn(1)->assertCreated();
    }

    public function test_partial_return_cancels_pending_coins_in_proportion(): void
    {
        Carbon::setTestNow($this->deliveredAt->copy()->addDays(2));
        $this->requestReturn(1)->assertCreated(); // 1 of 4 = 25% of 20 coins
        $return = OrderReturn::query()->firstOrFail();
        $refundBefore = (float) $return->refund_amount;

        $this->complete($return);

        $lot = $this->lot();
        $this->assertSame('pending', $lot->status);
        $this->assertSame(15, (int) $lot->amount);
        $this->assertEquals($refundBefore, (float) $return->fresh()->refund_amount); // refund formula untouched
        $this->assertSame(15, app(CoinLedgerService::class)->pendingBalance($this->user));
    }

    public function test_running_the_cancel_twice_does_not_cancel_more(): void
    {
        Carbon::setTestNow($this->deliveredAt->copy()->addDays(2));
        $this->requestReturn(2)->assertCreated(); // 50% of 20 = 10
        $return = OrderReturn::query()->firstOrFail();
        $this->complete($return);
        $this->assertSame(0, app(CoinLedgerService::class)->cancelPendingForReturns($this->order->fresh()));

        $this->assertSame(10, (int) $this->lot()->amount);
    }

    public function test_full_return_cancels_all_pending_coins(): void
    {
        Carbon::setTestNow($this->deliveredAt->copy()->addDays(2));
        $this->requestReturn(4)->assertCreated();
        $this->complete(OrderReturn::query()->firstOrFail());

        $lot = $this->lot();
        $this->assertSame('cancelled', $lot->status);
        $this->assertSame(0, (int) $lot->amount);
        $this->assertSame(0, app(CoinLedgerService::class)->pendingBalance($this->user));
    }

    public function test_pending_return_that_is_not_completed_cancels_nothing(): void
    {
        Carbon::setTestNow($this->deliveredAt->copy()->addDays(2));
        $this->requestReturn(2)->assertCreated();

        $this->assertSame(20, (int) $this->lot()->amount);
    }

    public function test_remaining_coins_unlock_after_a_partial_return_is_completed(): void
    {
        Carbon::setTestNow($this->deliveredAt->copy()->addDays(2));
        $this->requestReturn(1)->assertCreated();
        $this->complete(OrderReturn::query()->firstOrFail());

        app(CoinLedgerService::class)->unlockDueCoins($this->deliveredAt->copy()->addDays(8));

        $this->assertSame(15, (int) $this->user->fresh()->coins_balance);
    }

    public function test_cod_fee_and_shipping_do_not_change_the_cancel_ratio(): void
    {
        // Products 400, shipping 50, COD fee 30. Returning 1 of 4 units is 25%, not 100/480.
        Carbon::setTestNow($this->deliveredAt->copy()->addDays(2));
        $this->requestReturn(1)->assertCreated();
        $this->complete(OrderReturn::query()->firstOrFail());

        $this->assertSame(5, 20 - (int) $this->lot()->amount);
    }
}
